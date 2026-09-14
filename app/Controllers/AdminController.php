<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Reference;
use App\Models\User;

final class AdminController extends Controller
{
    private Reference $reference;

    public function __construct()
    {
        $this->reference = new Reference();
    }

    // ---------------------------------------------------------------
    // ผู้ใช้งาน
    // ---------------------------------------------------------------
    public function users(): void
    {
        $this->requireRole('admin');

        $users = new User();
        $editing = null;
        $editId = (int) q('edit', '0');
        if ($editId > 0) {
            $editing = $users->find($editId);
        }

        $this->view('admin/users', [
            'title'       => 'จัดการผู้ใช้งาน',
            'users'       => $users->listAll(),
            'departments' => $this->reference->departments(false),
            'editing'     => $editing,
        ]);
    }

    public function saveUser(): void
    {
        $this->requireRole('admin');
        $this->requireCsrf();

        $users = new User();
        $id = $this->intInput('id');
        $username = $this->input('username');
        $fullName = $this->input('full_name');

        if ($username === '' || $fullName === '') {
            flash('error', 'กรุณากรอกชื่อผู้ใช้และชื่อ-นามสกุล');
            redirect('/admin/users');
        }
        if ($users->usernameExists($username, $id)) {
            flash('error', 'ชื่อผู้ใช้ "' . $username . '" ถูกใช้ไปแล้ว');
            redirect('/admin/users');
        }

        $role = $this->input('role', 'staff');
        if (!isset(USER_ROLES[$role])) {
            $role = 'staff';
        }

        $password = (string) ($_POST['password'] ?? '');
        if ($id === null && $password === '') {
            flash('error', 'กรุณากำหนดรหัสผ่านสำหรับผู้ใช้ใหม่');
            redirect('/admin/users');
        }

        $users->save($id, [
            // ⚠️ เก็บ plain text ตามที่กำหนด — เปลี่ยนเป็น password_hash($password, PASSWORD_DEFAULT) ได้ที่นี่
            'password'      => $password,
            'username'      => $username,
            'full_name'     => $fullName,
            'position'      => $this->input('position') ?: null,
            'department_id' => $this->intInput('department_id'),
            'role'          => $role,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ]);

        flash('success', $id === null ? 'เพิ่มผู้ใช้เรียบร้อยแล้ว' : 'บันทึกการแก้ไขเรียบร้อยแล้ว');
        redirect('/admin/users');
    }

    public function deleteUser(string $id): void
    {
        $this->requireRole('admin');
        $this->requireCsrf();

        if ((int) $id === 1) {
            flash('error', 'ไม่สามารถลบบัญชีผู้ดูแลระบบหลักได้');
            redirect('/admin/users');
        }

        (new User())->remove((int) $id);
        flash('success', 'ลบผู้ใช้เรียบร้อยแล้ว');
        redirect('/admin/users');
    }

    // ---------------------------------------------------------------
    // ฝ่าย/กลุ่มงาน
    // ---------------------------------------------------------------
    public function departments(): void
    {
        $this->requireRole('admin');

        $editing = null;
        $editId = (int) q('edit', '0');
        if ($editId > 0) {
            $editing = $this->reference->findDepartment($editId);
        }

        $this->view('admin/departments', [
            'title'       => 'จัดการฝ่าย/กลุ่มงาน',
            'departments' => $this->reference->departments(false),
            'editing'     => $editing,
        ]);
    }

    public function saveDepartment(): void
    {
        $this->requireRole('admin');
        $this->requireCsrf();

        $name = $this->input('name');
        if ($name === '') {
            flash('error', 'กรุณากรอกชื่อฝ่าย');
            redirect('/admin/departments');
        }

        $this->reference->saveDepartment($this->intInput('id'), [
            'code'      => $this->input('code') ?: strtoupper(substr(md5($name), 0, 6)),
            'name'      => $name,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);

        flash('success', 'บันทึกฝ่าย/กลุ่มงานเรียบร้อยแล้ว');
        redirect('/admin/departments');
    }

    public function deleteDepartment(string $id): void
    {
        $this->requireRole('admin');
        $this->requireCsrf();

        $this->reference->removeDepartment((int) $id);
        flash('success', 'ลบฝ่าย/กลุ่มงานเรียบร้อยแล้ว');
        redirect('/admin/departments');
    }

    // ---------------------------------------------------------------
    // หน่วยงานภายนอก
    // ---------------------------------------------------------------
    public function organizations(): void
    {
        $this->requireRole('admin');

        $editing = null;
        $editId = (int) q('edit', '0');
        if ($editId > 0) {
            $editing = $this->reference->findOrganization($editId);
        }

        $this->view('admin/organizations', [
            'title'         => 'จัดการหน่วยงานภายนอก',
            'organizations' => $this->reference->organizations(false),
            'provinces'     => $this->reference->provinces(),
            'editing'       => $editing,
        ]);
    }

    public function saveOrganization(): void
    {
        $this->requireRole('admin');
        $this->requireCsrf();

        $name = $this->input('name');
        if ($name === '') {
            flash('error', 'กรุณากรอกชื่อหน่วยงาน');
            redirect('/admin/organizations');
        }

        $this->reference->saveOrganization($this->intInput('id'), [
            'name'        => $name,
            'province_id' => $this->intInput('province_id'),
            'address'     => $this->input('address') ?: null,
            'phone'       => $this->input('phone') ?: null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ]);

        flash('success', 'บันทึกหน่วยงานเรียบร้อยแล้ว');
        redirect('/admin/organizations');
    }

    public function deleteOrganization(string $id): void
    {
        $this->requireRole('admin');
        $this->requireCsrf();

        $this->reference->removeOrganization((int) $id);
        flash('success', 'ลบหน่วยงานเรียบร้อยแล้ว');
        redirect('/admin/organizations');
    }

    // ---------------------------------------------------------------
    // ตั้งค่าระบบ
    // ---------------------------------------------------------------
    public function settings(): void
    {
        $this->requireRole('admin');

        $this->view('admin/settings', [
            'title'    => 'ตั้งค่าหน่วยงาน',
            'settings' => $this->reference->settings(),
        ]);
    }

    public function saveSettings(): void
    {
        $this->requireRole('admin');
        $this->requireCsrf();

        $this->reference->saveSettings([
            'org_name'        => $this->input('org_name'),
            'org_code_prefix' => $this->input('org_code_prefix'),
            'org_address'     => $this->input('org_address'),
            'org_phone'       => $this->input('org_phone'),
        ]);

        flash('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
        redirect('/admin/settings');
    }
}
