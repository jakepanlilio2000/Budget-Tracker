<?php
namespace controllers;
use core\Controller;
use models\Profile;

class PreferenceController extends Controller {
    public function index(int $profile_id): void {
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile) $this->redirect('/');

        $this->view('preferences/index', ['profile' => $profile]);
    }
}