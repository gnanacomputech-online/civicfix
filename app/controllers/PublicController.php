<?php

require_once __DIR__ . '/../models/Complaint.php';

class PublicController
{
    /**
     * No-login transparency dashboard. Deliberately touches ONLY Complaint::publicStatsByWard()
     * and Complaint::publicStatsByDepartment() -- both aggregate-only queries that never select
     * a citizen name, contact detail, address, description, or photo column. See those methods
     * and this project's README for the PII-safety note.
     */
    public function dashboard(): void
    {
        $byWard = Complaint::publicStatsByWard();
        $byDepartment = Complaint::publicStatsByDepartment();

        $totals = Complaint::countsByStatus();
        $totalAll = array_sum($totals);
        $totalResolved = $totals['resolved'] + $totals['closed'];

        require __DIR__ . '/../views/public/dashboard.php';
    }
}
