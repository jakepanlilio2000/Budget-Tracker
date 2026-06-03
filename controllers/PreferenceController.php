<?php
namespace controllers;
use core\Controller;
use models\Profile;
use config\Database;

class PreferenceController extends Controller {
    
    public function index(int $profile_id): void {
        $profileModel = new Profile();
        $profile = $profileModel->find($profile_id);
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) $this->redirect('/');

        $this->view('preferences/index', ['profile' => $profile]);
    }
    
    public function toggle(int $profile_id): void {
        $this->checkCsrf();
        $profileModel = new \models\Profile();
        $profile = $profileModel->find($profile_id);
        
        if (!$profile || $profile['user_id'] !== $_SESSION['user_id']) {
            echo json_encode(['success' => false]);
            exit;
        }

        $key = $_POST['key'] ?? '';
        $state = (int)$_POST['state']; 
        
        $allowed = ['pref_privacy', 'pref_animations', 'pref_compact', 'pref_zen'];
        if (!in_array($key, $allowed)) {
            echo json_encode(['success' => false]);
            exit;
        }

        $db = \config\Database::getInstance();
        $stmt = $db->prepare("UPDATE profiles SET $key = :state WHERE id = :id");
        $success = $stmt->execute(['state' => $state, 'id' => $profile_id]);
        
        echo json_encode(['success' => $success]);
        exit;
    }
}