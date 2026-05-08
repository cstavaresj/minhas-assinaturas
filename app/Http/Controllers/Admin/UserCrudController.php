<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Requests\UserRequest;
use App\Services\PasswordSecurityService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/users');
        CRUD::setEntityNameStrings('usuário', 'usuários');
    }

    protected function setupListOperation()
    {
        CRUD::column('name')->label('Nome');
        CRUD::column('email')->label('E-mail');
        CRUD::column('role_label')->label('Perfil');
        CRUD::column('status_label')->label('Status');
        CRUD::column('created_at')->label('Cadastro')->type('datetime');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(UserRequest::class);

        $this->setupFormFields(isUpdate: false);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(UserRequest::class);

        $this->setupFormFields(isUpdate: true);
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        $data = $this->crud->getStrippedSaveRequest($request);
        $data['lgpd_consent_at'] = now();
        $data['password'] = PasswordSecurityService::hashPassword((string) $data['password']);
        $data['created_via_google'] = false;

        $item = $this->crud->create($data);
        $this->syncRole($item, (string) $request->input('role', 'user'));

        $this->data['entry'] = $this->crud->entry = $item;

        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        $data = $this->crud->getStrippedSaveRequest($request);

        if (blank($request->input('password'))) {
            unset($data['password']);
        } else {
            $data['password'] = PasswordSecurityService::hashPassword((string) $data['password']);
            $data['created_via_google'] = false;
        }

        $item = $this->crud->update(
            $request->input($this->crud->model->getKeyName()),
            $data
        );

        $this->syncRole($item, (string) $request->input('role', 'user'));
        $this->data['entry'] = $this->crud->entry = $item;

        \Alert::success(trans('backpack::crud.update_success'))->flash();

        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        if ((string) $id === (string) backpack_user()?->getKey()) {
            abort(403, 'Você não pode excluir a própria conta pelo painel administrativo.');
        }

        return $this->crud->delete($id);
    }

    protected function setupFormFields(bool $isUpdate): void
    {
        $currentUser = $isUpdate && request()->route('id')
            ? User::find(request()->route('id'))
            : null;

        CRUD::field('name')->label('Nome')->type('text')->attributes(['placeholder' => 'Nome completo']);
        CRUD::field('email')->label('E-mail')->type('email')->attributes(['placeholder' => 'nome@exemplo.com']);
        CRUD::field('password')->label('Senha')->type('password');
        CRUD::field('password_confirmation')->label('Confirmar senha')->type('password');
        CRUD::field('role')->label('Perfil')->type('select_from_array')->options([
            'admin' => 'Administrador',
            'user' => 'Usuário',
        ])->value($currentUser?->getRoleNames()->first() ?? 'user');
        CRUD::field('status')->label('Status')->type('select_from_array')->options([
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'blocked' => 'Bloqueado',
        ])->default('active');

        if ($isUpdate) {
            CRUD::field('password')->hint('Deixe em branco para manter a senha atual.');
        }
    }

    protected function syncRole(User $user, string $role): void
    {
        $user->syncRoles([$role]);
    }

}
