<?php

namespace App\Http\Controllers;

use App\Services\StatistiquesCollecte;
use Illuminate\View\View;

class StatistiquesCollecteController extends Controller
{
    public function __invoke(StatistiquesCollecte $statistiques): View
    {
        return view('collecte.statistiques', $statistiques->tableauDeBord());
    }
}
