<?php
/**
 * ProfilMitraController
 */

class ProfilMitraController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Profil Mitra - PT. Polo Sport Indonesia'
        ];

        return $this->view('profil_mitra/index', $data);
    }
}
