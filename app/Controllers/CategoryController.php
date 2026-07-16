<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Models\Category;

class CategoryController extends Controller
{
    public function __construct()
    {
        if (!Auth::check())
            $this->redirect('/login');
    }

    public function index(): void
    {
        $categories = Category::getAllByUser(Auth::id());
        $this->view('categories.index', ['categories' => $categories]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? 'expense',
            'parent_id' => !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null,
            'color' => $_POST['color'] ?? '#3b82f6',
            'icon' => $_POST['icon'] ?? 'fa-tag'
        ];

        if (empty($data['name'])) {
            Session::set('error', 'Category name is required.');
            $this->redirect('/categories');
        }

        Category::create(Auth::id(), $data);
        Session::set('success', 'Category created successfully.');
        $this->redirect('/categories');
    }

    public function archive(int $id): void
    {
        $this->validateCsrf();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE categories SET is_archived = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, Auth::id()]);
        Session::set('success', 'Category archived.');
        $this->redirect('/categories');
    }

    public function delete(int $id): void
    {
        $this->validateCsrf();
        $userId = Auth::id();

        $category = Category::findById($id, $userId);
        if (!$category) {
            Session::set('error', 'Category not found.');
            $this->redirect('/categories');
        }

        if (!$category['is_archived']) {
            Session::set('error', 'Category must be archived before permanent deletion.');
            $this->redirect('/categories');
        }

        if (Category::permanentlyDelete($id, $userId)) {
            Session::set('success', 'Category permanently deleted.');
        } else {
            Session::set('error', 'Failed to delete category.');
        }

        $this->redirect('/categories');
    }
}