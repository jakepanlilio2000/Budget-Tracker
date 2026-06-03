<?php
namespace controllers;
use core\Controller;
use models\Category;

class CategoryController extends Controller {
    public function index(int $profile_id): void {
        $profileModel = new \models\Profile();
        $profile = $profileModel->find($profile_id);
        $catModel = new Category();
        $categories = $catModel->findAll(['profile_id' => $profile_id], 'sort_order ASC');
        $this->view('categories/index', ['profile' => $profile, 'categories' => $categories, 'profile_id' => $profile_id]);
    }

    public function store(int $profile_id): void {
        $this->checkCsrf();
        $catModel = new Category();
        $catModel->create([
            'profile_id' => $profile_id,
            'name' => htmlspecialchars($_POST['name']),
            'type' => $_POST['type'],
            'color' => htmlspecialchars($_POST['color']),
            'icon' => htmlspecialchars($_POST['icon'])
        ]);
        $this->redirect("/categories/{$profile_id}");
    }

    public function reorder(int $profile_id): void {
        $this->checkCsrf();
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['ids']) && is_array($input['ids'])) {
            $catModel = new Category();
            $catModel->reorder($profile_id, $input['ids']);
            $this->json(['success' => true]);
        }
        $this->json(['error' => 'Invalid data'], 400);
    }

    public function edit(int $id): void {
        $catModel = new \models\Category();
        $category = $catModel->find($id);
        if (!$category) $this->redirect('/');

        $this->view('categories/edit', [
            'category' => $category,
            'profile_id' => $category['profile_id']
        ]);
    }

    public function update(int $id): void {
        $this->checkCsrf();
        $catModel = new \models\Category();
        $category = $catModel->find($id);
        if (!$category) $this->redirect('/');

        $catModel->update($id, [
            'name' => htmlspecialchars($_POST['name']),
            'type' => $_POST['type'],
            'color' => htmlspecialchars($_POST['color']),
            'icon' => htmlspecialchars($_POST['icon'])
        ]);

        $this->redirect("/categories/{$category['profile_id']}");
    }

    public function delete(int $id): void {
        $this->checkCsrf();
        $catModel = new \models\Category();
        $category = $catModel->find($id);
        if (!$category) {
            $this->json(['success' => false, 'error' => 'Category not found'], 404);
        }

        $db = \config\Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM entries WHERE category_id = :cid");
        $stmt->execute(['cid' => $id]);
        
        if ((int)$stmt->fetchColumn() > 0) {
            $this->json(['success' => false, 'error' => 'Cannot delete category. Budget entries are currently using it.'], 400);
            return;
        }

        $catModel->delete($id);
        $this->json(['success' => true]);
    }
}