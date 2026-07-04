<?php

namespace App\Http\Controllers\Admin;

/**
 * Recap (rekap) of approved piket records for a client, grouped by month.
 * Extends LemburRekapController — see that class for the shared logic;
 * only $type differs, which drives the type='piket' filtering, the
 * "admin.rekap-piket.*" route names, and the "admin.rekap-piket.*" views.
 */
class PiketRekapController extends LemburRekapController
{
    protected string $type = 'piket';
}
