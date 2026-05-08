<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class ManageCategories extends Component
{
    // use WithPagination;
    public int $page = 1;
    public int $perPage = 10;

    public $search = '';
    public $categoryId;
    public $name, $icon, $color;
    public $isEditing = false;
    public $showDeleteModal = false;
    public $deletingName = '';

    protected $rules = [
        'name' => 'required|min:3|max:80|unique:categories,name',
        'icon' => 'required',
        'color' => 'required',
    ];

    public function resetFields()
    {
        $this->name = '';
        $this->icon = '';
        $this->color = '#10b981';
        $this->categoryId = null;
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function gotoPage($page)
    {
        $this->page = $page;
    }

    public function nextPage()
    {
        $this->page++;
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function create()
    {
        $this->resetFields();
        $this->isEditing = false;
        $this->dispatch('open-category-modal');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $this->categoryId = $id;
        $this->name = $category->name;
        $this->icon = $category->icon;
        $this->color = $category->color;
        $this->isEditing = true;
        
        $this->dispatch('open-category-modal');
    }

    public function save()
    {
        $rules = $this->rules;
        if ($this->isEditing) {
            $rules['name'] = 'required|min:3|max:80|unique:categories,name,' . $this->categoryId;
        }

        $this->validate($rules);

        Category::updateOrCreate(
            ['id' => $this->categoryId],
            [
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'icon' => $this->icon,
                'color' => $this->color,
                'is_system' => true,
                'privacy_token' => null,
            ]
        );

        session()->flash('success', $this->isEditing ? 'Categoria atualizada!' : 'Categoria criada com sucesso!');
        
        $this->dispatch('close-category-modal');
        $this->resetFields();
    }

    public function confirmDelete($id)
    {
        $category = Category::findOrFail($id);
        $this->categoryId = $id;
        $this->deletingName = $category->name;
        $this->showDeleteModal = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->categoryId = null;
        $this->deletingName = '';
    }

    public function delete()
    {
        if (!$this->categoryId) return;

        $category = Category::findOrFail($this->categoryId);
        
        // Impedir exclusão se houver assinaturas vinculadas
        if ($category->subscriptions()->count() > 0) {
            session()->flash('error', 'Não é possível excluir uma categoria que possui assinaturas vinculadas.');
            $this->showDeleteModal = false;
            return;
        }

        $category->delete();
        session()->flash('success', 'Categoria excluída com sucesso.');
        $this->showDeleteModal = false;
        $this->resetFields();
    }

    public function render()
    {
        $query = Category::where('is_system', true)
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('name');

        $total = $query->count();
        $categories = $query->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();

        return view('livewire.admin.manage-categories', [
            'categories' => $categories,
            'totalPages' => ceil($total / $this->perPage),
            'totalRecords' => $total
        ]);
    }
}
