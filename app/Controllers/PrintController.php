<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Assignment;
use App\Models\Document;
use App\Models\Reference;

final class PrintController extends Controller
{
    /** ใบปะหน้า/ใบเกษียณหนังสือ — A4 แนวตั้ง */
    public function cover(string $id): void
    {
        $this->requireLogin();
        $docId = (int) $id;

        $documents = new Document();
        $doc = $documents->findFull($docId);
        if ($doc === null) {
            $this->abort(404, 'ไม่พบหนังสือที่ต้องการ');
        }

        $assignments = new Assignment();
        if (!Auth::isClerk()
            && !$assignments->userCanAccessDocument($docId, (int) Auth::id(), Auth::departmentId())) {
            $this->abort(403, 'คุณไม่มีสิทธิ์พิมพ์หนังสือฉบับนี้');
        }

        $this->view('print/cover', [
            'title'       => 'ใบปะหน้าหนังสือ ' . reg_label((int) $doc['reg_number'], (int) $doc['reg_year']),
            'doc'         => $doc,
            'assignments' => $assignments->forDocument($docId),
            'settings'    => (new Reference())->settings(),
        ], 'print_portrait');
    }

    /** ทะเบียนคุมหนังสือรับ/ส่ง — A4 แนวนอน */
    public function register(): void
    {
        $this->requireLogin();

        $direction = q('direction', 'incoming');
        if (!isset(DOC_DIRECTIONS[$direction])) {
            $direction = 'incoming';
        }

        $filters = [
            'direction' => $direction,
            'year'      => q('year', (string) be_year()),
            'date_from' => q('date_from'),
            'date_to'   => q('date_to'),
            'status'    => q('status'),
            'speed'     => q('speed'),
            'q'         => q('q'),
        ];

        if (!Auth::isClerk()) {
            $filters['restrict_user_id']       = Auth::id();
            $filters['restrict_department_id'] = Auth::departmentId();
        }

        $this->view('print/register', [
            'title'     => 'ทะเบียน' . DOC_DIRECTIONS[$direction] . ' ปี ' . $filters['year'],
            'rows'      => (new Document())->searchAll($filters),
            'filters'   => $filters,
            'direction' => $direction,
            'settings'  => (new Reference())->settings(),
        ], 'print_landscape');
    }
}
