<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Assignment;
use App\Models\Attachment;
use App\Models\DocLog;
use App\Models\Document;
use App\Models\Reference;
use App\Models\User;

final class DocumentController extends Controller
{
    private Document $documents;
    private Reference $reference;

    public function __construct()
    {
        $this->documents = new Document();
        $this->reference = new Reference();
    }

    // ---------------------------------------------------------------
    // ทะเบียนหนังสือ
    // ---------------------------------------------------------------
    public function index(): void
    {
        $this->requireLogin();

        $view = q('view');
        if ($view !== '' && !isset(DOC_VIEWS[$view])) {
            $view = '';
        }

        $filters = [
            'direction'  => q('direction'),
            'year'       => q('year'),
            'status'     => q('status'),
            'speed'      => q('speed'),
            'doc_type'   => q('doc_type'),
            'department' => q('department'),
            'date_from'  => q('date_from'),
            'date_to'    => q('date_to'),
            'q'          => q('q'),
            'view'       => $view,
        ];

        // staff เห็นเฉพาะหนังสือที่เกษียณถึงตนเอง/ฝ่ายตน
        if (!Auth::isClerk()) {
            $filters['restrict_user_id']       = Auth::id();
            $filters['restrict_department_id'] = Auth::departmentId();
        }
        $filters['my_department_id'] = Auth::departmentId();

        $page = max(1, (int) q('page', '1'));
        $perPage = 20;
        $result = $this->documents->search($filters, $page, $perPage);

        // นับจำนวนของแต่ละมุมมอง โดยคงตัวกรองอื่นที่ผู้ใช้เปิดอยู่ไว้
        $countBase = $filters;
        unset($countBase['view']);

        $this->view('documents/index', [
            'title'       => $filters['direction'] !== '' && isset(DOC_DIRECTIONS[$filters['direction']])
                ? 'ทะเบียน' . DOC_DIRECTIONS[$filters['direction']]
                : 'ทะเบียนหนังสือทั้งหมด',
            'rows'        => $result['rows'],
            'total'       => $result['total'],
            'page'        => $page,
            'perPage'     => $perPage,
            'filters'     => $filters,
            'activeView'  => $view,
            'viewCounts'  => $this->documents->viewCounts($countBase),
            'hasDept'     => Auth::departmentId() !== null,
            'years'       => $this->documents->years(),
            'departments' => $this->reference->departments(),
        ]);
    }

    // ---------------------------------------------------------------
    // ลงทะเบียนหนังสือใหม่
    // ---------------------------------------------------------------
    public function create(): void
    {
        $this->requireClerk();

        $direction = q('direction', 'incoming');
        if (!isset(DOC_DIRECTIONS[$direction])) {
            $direction = 'incoming';
        }

        // ค่าที่จำไว้จากฉบับก่อน — ลงหนังสือติดกันหลายฉบับจะได้ไม่ต้องเลือกซ้ำ
        $remember = $_SESSION['_doc_defaults'][$direction] ?? [];
        $justSaved = $_SESSION['_doc_just_saved'] ?? null;
        $old = $_SESSION['_doc_old'] ?? [];
        unset($_SESSION['_doc_just_saved'], $_SESSION['_doc_old']);

        $this->view('documents/create', [
            'title'         => 'ลงทะเบียน' . DOC_DIRECTIONS[$direction],
            'direction'     => $direction,
            'doc'           => null,
            'old'           => $old,
            'remember'      => $remember,
            'justSaved'     => $justSaved,
            'nextReg'       => $this->documents->nextRegNumber($direction, be_year()),
            'organizations' => $this->reference->organizations(),
            'departments'   => $this->reference->departments(),
            'settings'      => $this->reference->settings(),
        ]);
    }

    public function store(): void
    {
        $this->requireClerk();
        $this->requireCsrf();

        $direction = $this->input('direction', 'incoming');
        if (!isset(DOC_DIRECTIONS[$direction])) {
            $direction = 'incoming';
        }

        $subject = $this->input('subject');
        if ($subject === '') {
            // เก็บสิ่งที่พิมพ์ไว้ก่อน จะได้ไม่ต้องกรอกใหม่ทั้งใบ
            $this->keepOldInput();
            flash('error', 'กรุณากรอกชื่อเรื่อง');
            redirect('/documents/create?direction=' . $direction);
        }

        $regDatetime = parse_thai_datetime($this->input('reg_datetime')) ?? date('Y-m-d H:i:s');

        $input = $this->collectInput();
        $input['reg_datetime'] = $regDatetime;

        $created = $this->documents->create(
            $direction,
            be_year($regDatetime),
            $input,
            Auth::id()
        );

        $label = reg_label($created['reg_number'], $created['reg_year']);

        // จำเฉพาะค่าที่ซ้ำกันจริงระหว่างฉบับ — ชั้นความเร็ว/ความลับไม่จำ เพราะต้องดูจากหนังสือทีละฉบับ
        $_SESSION['_doc_defaults'][$direction] = [
            'doc_type'            => $input['doc_type'],
            'owner_department_id' => $this->input('remember_department') !== ''
                ? $input['owner_department_id']
                : null,
        ];

        if ($this->input('save_next') !== '') {
            $_SESSION['_doc_just_saved'] = ['id' => $created['id'], 'label' => $label];
            redirect('/documents/create?direction=' . $direction);
        }

        flash('success', sprintf(
            'ลงทะเบียน%sเลขที่ %s เรียบร้อยแล้ว',
            DOC_DIRECTIONS[$direction],
            $label
        ));
        redirect('/documents/' . $created['id']);
    }

    // ---------------------------------------------------------------
    // รายละเอียดหนังสือ
    // ---------------------------------------------------------------
    public function show(string $id): void
    {
        $this->requireLogin();
        $docId = (int) $id;

        $doc = $this->documents->findFull($docId);
        if ($doc === null) {
            $this->abort(404, 'ไม่พบหนังสือที่ต้องการ');
        }

        $assignments = new Assignment();
        $this->guardAccess($docId, $assignments);

        $this->view('documents/show', [
            'title'       => 'หนังสือเลขที่ ' . reg_label((int) $doc['reg_number'], (int) $doc['reg_year']),
            'doc'         => $doc,
            'assignments' => $assignments->forDocument($docId),
            'attachments' => (new Attachment())->forDocument($docId),
            'logs'        => (new DocLog())->forDocument($docId),
            'departments' => $this->reference->departments(),
            'users'       => (new User())->selectable(),
        ]);
    }

    // ---------------------------------------------------------------
    // แก้ไขหนังสือ
    // ---------------------------------------------------------------
    public function edit(string $id): void
    {
        $this->requireClerk();
        $docId = (int) $id;

        $doc = $this->documents->findFull($docId);
        if ($doc === null) {
            $this->abort(404, 'ไม่พบหนังสือที่ต้องการ');
        }

        $old = $_SESSION['_doc_old'] ?? [];
        unset($_SESSION['_doc_old']);

        $this->view('documents/create', [
            'title'         => 'แก้ไขหนังสือเลขที่ ' . reg_label((int) $doc['reg_number'], (int) $doc['reg_year']),
            'direction'     => $doc['direction'],
            'doc'           => $doc,
            'old'           => $old,
            'remember'      => [],
            'justSaved'     => null,
            'nextReg'       => null,
            'organizations' => $this->reference->organizations(),
            'departments'   => $this->reference->departments(),
            'settings'      => $this->reference->settings(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireClerk();
        $this->requireCsrf();
        $docId = (int) $id;

        $doc = $this->documents->findFull($docId);
        if ($doc === null) {
            $this->abort(404, 'ไม่พบหนังสือที่ต้องการ');
        }

        if ($this->input('subject') === '') {
            $this->keepOldInput();
            flash('error', 'กรุณากรอกชื่อเรื่อง');
            redirect('/documents/' . $docId . '/edit');
        }

        $this->documents->updateDocument($docId, $this->collectInput());
        (new DocLog())->add($docId, Auth::id(), 'updated', 'แก้ไขข้อมูลหนังสือ');

        flash('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
        redirect('/documents/' . $docId);
    }

    // ---------------------------------------------------------------
    // เกษียณ/มอบหมาย
    // ---------------------------------------------------------------
    public function assign(string $id): void
    {
        $this->requireClerk();
        $this->requireCsrf();
        $docId = (int) $id;

        $doc = $this->documents->findFull($docId);
        if ($doc === null) {
            $this->abort(404, 'ไม่พบหนังสือที่ต้องการ');
        }

        $departmentId = $this->intInput('department_id');
        $assignedTo   = $this->intInput('assigned_to');

        if ($departmentId === null && $assignedTo === null) {
            flash('error', 'กรุณาเลือกฝ่ายหรือผู้รับผิดชอบอย่างน้อยหนึ่งอย่าง');
            redirect('/documents/' . $docId);
        }

        (new Assignment())->create([
            'document_id'   => $docId,
            'department_id' => $departmentId,
            'assigned_to'   => $assignedTo,
            'instruction'   => $this->input('instruction') ?: null,
            'assigned_by'   => Auth::id(),
            'status'        => 'pending',
        ]);

        // หนังสือที่เพิ่งเกษียณถือว่าเริ่มดำเนินการแล้ว
        if ($doc['status'] === 'pending') {
            $this->documents->changeStatus($docId, 'in_progress');
        }

        $target = $departmentId !== null
            ? ($this->reference->findDepartment($departmentId)['name'] ?? 'ฝ่าย')
            : 'ผู้รับผิดชอบที่ระบุ';

        (new DocLog())->add($docId, Auth::id(), 'assigned', 'เกษียณถึง ' . $target);

        flash('success', 'เกษียณหนังสือเรียบร้อยแล้ว');
        redirect('/documents/' . $docId);
    }

    // ---------------------------------------------------------------
    // เปลี่ยนสถานะหนังสือ
    // ---------------------------------------------------------------
    public function changeStatus(string $id): void
    {
        $this->requireClerk();
        $this->requireCsrf();
        $docId = (int) $id;

        $status = $this->input('status');
        if (!isset(DOC_STATUSES[$status])) {
            flash('error', 'สถานะไม่ถูกต้อง');
            redirect('/documents/' . $docId);
        }

        $this->documents->changeStatus($docId, $status);
        (new DocLog())->add($docId, Auth::id(), 'status', 'เปลี่ยนสถานะเป็น ' . DOC_STATUSES[$status]);

        flash('success', 'อัปเดตสถานะเป็น "' . DOC_STATUSES[$status] . '" แล้ว');
        // กดจากในแถวของหน้าทะเบียน ให้กลับไปที่หน้าเดิมพร้อมตัวกรองเดิม
        redirect($this->safeBack() ?? '/documents/' . $docId);
    }

    // ---------------------------------------------------------------
    // ผู้รับมอบหมายรายงานผลการดำเนินการ
    // ---------------------------------------------------------------
    public function respondAssignment(string $id): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $assignments = new Assignment();
        $assignment = $assignments->find((int) $id);
        if ($assignment === null) {
            $this->abort(404, 'ไม่พบรายการมอบหมาย');
        }

        $docId = (int) $assignment['document_id'];

        // ต้องเป็นผู้รับมอบหมาย/คนในฝ่ายที่รับผิดชอบ หรือเจ้าหน้าที่สารบรรณ
        $isOwner = ((int) ($assignment['assigned_to'] ?? 0) === (int) Auth::id())
            || ($assignment['department_id'] !== null
                && (int) $assignment['department_id'] === (int) Auth::departmentId());

        if (!$isOwner && !Auth::isClerk()) {
            $this->abort(403, 'คุณไม่มีสิทธิ์รายงานผลของงานนี้');
        }

        $status = $this->input('status');
        if (!isset(DOC_STATUSES[$status])) {
            flash('error', 'สถานะไม่ถูกต้อง');
            redirect('/documents/' . $docId);
        }

        $note = $this->input('response_note');
        $assignments->respond((int) $id, $status, $note);

        // สรุปสถานะของหนังสือจากงานที่มอบหมายทั้งหมด
        $all = $assignments->forDocument($docId);
        $open = array_filter($all, static fn(array $a): bool => in_array($a['status'], ['pending', 'in_progress'], true));
        $this->documents->changeStatus($docId, $open === [] ? 'done' : 'in_progress');

        (new DocLog())->add(
            $docId,
            Auth::id(),
            'responded',
            'รายงานผล: ' . DOC_STATUSES[$status] . ($note !== '' ? ' — ' . mb_substr($note, 0, 200) : '')
        );

        flash('success', 'บันทึกผลการดำเนินการเรียบร้อยแล้ว');
        redirect('/documents/' . $docId);
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    /**
     * เก็บค่าที่พิมพ์ไว้ชั่วคราวเมื่อบันทึกไม่ผ่าน แล้วเอากลับมาเติมในฟอร์ม
     * ไม่เก็บ token เพราะหน้าใหม่จะออก token ของตัวเอง
     */
    private function keepOldInput(): void
    {
        $old = $_POST;
        unset($old['_csrf']);
        $_SESSION['_doc_old'] = $old;
    }

    /** ที่อยู่ปลายทางหลังบันทึก รับเฉพาะเส้นทางภายในระบบเท่านั้น */
    private function safeBack(): ?string
    {
        $back = $this->input('back');
        if ($back === '' || $back[0] !== '/' || strncmp($back, '//', 2) === 0) {
            return null;
        }
        return $back;
    }

    /** รวบรวมค่าจากฟอร์มลงทะเบียน/แก้ไข */
    private function collectInput(): array
    {
        $docType = $this->input('doc_type');
        $speed   = $this->input('speed');
        $secrecy = $this->input('secrecy');

        // ผู้ส่ง/ผู้รับใช้ช่องเดียว: ถ้าเลือกหน่วยงานจากรายการได้แล้ว ไม่ต้องเก็บข้อความซ้ำอีก
        $fromOrgId = $this->intInput('from_org_id');
        $toOrgId   = $this->intInput('to_org_id');

        return [
            'doc_number'          => $this->input('doc_number') ?: null,
            'doc_date'            => parse_thai_date($this->input('doc_date')),
            'from_org_id'         => $fromOrgId,
            'from_text'           => $fromOrgId !== null ? null : ($this->input('from_text') ?: null),
            'to_org_id'           => $toOrgId,
            'to_text'             => $toOrgId !== null ? null : ($this->input('to_text') ?: null),
            'subject'             => $this->input('subject'),
            'detail'              => $this->input('detail') ?: null,
            'doc_type'            => in_array($docType, DOC_TYPES, true) ? $docType : 'ภายนอก',
            'speed'               => in_array($speed, DOC_SPEEDS, true) ? $speed : 'ปกติ',
            'secrecy'             => in_array($secrecy, DOC_SECRECY, true) ? $secrecy : 'ปกติ',
            'owner_department_id' => $this->intInput('owner_department_id'),
            'signer'              => $this->input('signer') ?: null,
        ];
    }

    /** staff เห็นเฉพาะหนังสือที่เกี่ยวข้องกับตนเอง/ฝ่ายตน */
    private function guardAccess(int $docId, Assignment $assignments): void
    {
        if (Auth::isClerk()) {
            return;
        }
        $ok = $assignments->userCanAccessDocument($docId, (int) Auth::id(), Auth::departmentId());
        if (!$ok) {
            $this->abort(403, 'หนังสือฉบับนี้ไม่ได้มอบหมายถึงคุณหรือฝ่ายของคุณ');
        }
    }
}
