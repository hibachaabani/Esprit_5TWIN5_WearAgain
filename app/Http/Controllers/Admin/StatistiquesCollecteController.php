<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatistiquesCollecte;
use Illuminate\View\View;

class StatistiquesCollecteController extends Controller
{
    public function __invoke(StatistiquesCollecte $statistiques): View
    {
        return view('admin.collecte.statistiques', $statistiques->tableauDeBord());
    }
}
