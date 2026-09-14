<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Assignment;
use App\Models\Document;
use App\Models\Reference;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $documents = new Document();
        $year = be_year();

        $this->view('dashboard/index', [
            'title'    => 'หน้าแรก',
            'stats'    => $documents->stats($year),
            'year'     => $year,
            'recent'   => Auth::isClerk() ? $documents->recent(8) : [],
            'myTasks'  => (new Assignment())->myTasks(
                (int) Auth::id(),
                Auth::departmentId(),
                true,
                20
            ),
            'settings' => (new Reference())->settings(),
        ]);
    }
}
