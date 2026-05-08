<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class T18_GerenciarCategoriasTeste extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar role admin se não existir
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }

        $this->admin = $this->createAdmin();
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    /**
     * Admin pode acessar tela de manage categories
     */
    public function test_072_admin_pode_acessar_gerenciamento_de_categorias()
    {
        $this->actingAs($this->admin)
            ->get(route('admin.categories'))
            ->assertOk();
    }

    /**
     * Usuário normal não acessa manage categories
     */
    public function test_073_usuario_normal_nao_acessa_gerenciamento_de_categorias()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('admin.categories'))
            ->assertRedirect(route('dashboard'));
    }

    /**
     * Criar categoria com nome válido
     */
    public function test_074_criar_categoria_com_nome_valido()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Streaming')
            ->set('icon', 'tv')
            ->set('color', '#ff0000')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'name' => 'Streaming',
            'icon' => 'tv',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);
    }

    /**
     * Criar categoria com nome < 3 chars falha
     */
    public function test_075_criar_categoria_com_nome_muito_curto()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'AB')
            ->set('icon', 'tv')
            ->set('color', '#ff0000')
            ->call('save')
            ->assertHasErrors('name');
    }

    /**
     * Criar categoria com nome oversized (>80) falha
     */
    public function test_076_criar_categoria_com_nome_muito_longo()
    {
        $longName = str_repeat('a', 81);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', $longName)
            ->set('icon', 'tv')
            ->set('color', '#ff0000')
            ->call('save')
            ->assertHasErrors(['name' => 'max']);
    }

    /**
     * Criar categoria com nome contendo XSS payload
     */
    public function test_077_criar_categoria_com_nome_payload_xss()
    {
        $maliciousName = '<img src=x onerror=alert(1)>';

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', $maliciousName)
            ->set('icon', 'tv')
            ->set('color', '#ff0000')
            ->call('save');

        // Nome é armazenado (validação não bloqueia HTML, apenas renderização escapa)
        $this->assertDatabaseHas('categories', ['name' => $maliciousName]);
    }

    /**
     * Nome XSS é escapado na renderização
     */
    public function test_078_nome_xss_escapado_na_tela()
    {
        Category::create([
            'name' => '<script>alert("xss")</script>',
            'slug' => 'xss-test',
            'icon' => 'tv',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.categories'));

        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $response->getContent());
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $response->getContent());
    }

    /**
     * Criar categoria com nome duplicado falha
     */
    public function test_079_criar_categoria_com_nome_duplicado()
    {
        Category::create([
            'name' => 'Streaming',
            'slug' => 'streaming',
            'icon' => 'tv',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Streaming')
            ->set('icon', 'tv')
            ->set('color', '#ff0000')
            ->call('save')
            ->assertHasErrors('name');
    }

    /**
     * Criar categoria sem icon falha
     */
    public function test_080_criar_categoria_sem_icone()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Streaming')
            ->set('icon', '')
            ->set('color', '#ff0000')
            ->call('save')
            ->assertHasErrors('icon');
    }

    /**
     * Criar categoria sem color falha
     */
    public function test_081_criar_categoria_sem_cor()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Streaming')
            ->set('icon', 'tv')
            ->set('color', '')
            ->call('save')
            ->assertHasErrors('color');
    }

    /**
     * Criar categoria com icon com SQL injection literal é salvo como string
     */
    public function test_082_icone_com_sql_injection_salvo_como_string()
    {
        $sqlPayload = "tv'; DROP TABLE categories; --";

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Streaming')
            ->set('icon', $sqlPayload)
            ->set('color', '#ff0000')
            ->call('save');

        $this->assertDatabaseHas('categories', ['icon' => $sqlPayload]);
    }

    /**
     * Color com hex inválido (não validado estritamente, apenas stored)
     */
    public function test_083_cor_com_valor_invalido()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Streaming')
            ->set('icon', 'tv')
            ->set('color', 'not-a-color')
            ->call('save');

        $this->assertDatabaseHas('categories', ['color' => 'not-a-color']);
    }

    /**
     * Editar categoria com nome válido
     */
    public function test_084_editar_categoria()
    {
        $cat = Category::create([
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'icon' => 'play',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->call('edit', $cat->id)
            ->set('name', 'Streaming')
            ->set('icon', 'tv')
            ->set('color', '#00ff00')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'id' => $cat->id,
            'name' => 'Streaming',
            'icon' => 'tv',
            'color' => '#00ff00',
        ]);
    }

    /**
     * Editar categoria: nome duplicado com outra categoria falha
     */
    public function test_085_editar_categoria_nome_duplicado_com_outra()
    {
        $cat1 = Category::create([
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'icon' => 'play',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        $cat2 = Category::create([
            'name' => 'Streaming',
            'slug' => 'streaming',
            'icon' => 'tv',
            'color' => '#00ff00',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->call('edit', $cat1->id)
            ->set('name', 'Streaming')
            ->set('icon', 'tv')
            ->call('save')
            ->assertHasErrors('name');
    }

    /**
     * Editar categoria: pode reutilizar o mesmo nome (unique,id)
     */
    public function test_086_editar_categoria_pode_manter_mesmo_nome()
    {
        $cat = Category::create([
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'icon' => 'play',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->call('edit', $cat->id)
            ->set('name', 'Entertainment')
            ->set('icon', 'tv')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'id' => $cat->id,
            'name' => 'Entertainment',
        ]);
    }

    /**
     * Deletar categoria sem assinaturas vinculadas funciona
     */
    public function test_087_deletar_categoria_sem_assinaturas()
    {
        $cat = Category::create([
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'icon' => 'play',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->call('confirmDelete', $cat->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('deletingName', 'Entertainment')
            ->call('delete');

        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    /**
     * Deletar categoria com assinaturas vinculadas falha
     */
    public function test_088_deletar_categoria_com_assinaturas_falha()
    {
        $user = User::factory()->create();
        $cat = Category::create([
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'icon' => 'play',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Subscription::create([
            'privacy_token' => $user->privacyToken?->token ?? 'token',
            'name' => 'Netflix',
            'category_id' => $cat->id,
            'billing_cycle' => 'monthly',
            'amount' => 19.90,
            'currency' => 'BRL',
            'start_date' => now(),
            'status' => 'active',
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->call('confirmDelete', $cat->id)
            ->call('delete')
            ->assertSet('showDeleteModal', false);

        // Categoria deve permanecer no banco (exclusão impedida)
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
    }

    /**
     * Cancel delete limpa o modal
     */
    public function test_089_cancelar_exclusao_limpa_modal()
    {
        $cat = Category::create([
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'icon' => 'play',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->call('confirmDelete', $cat->id)
            ->assertSet('showDeleteModal', true)
            ->call('cancelDelete')
            ->assertSet('showDeleteModal', false)
            ->assertSet('categoryId', null)
            ->assertSet('deletingName', '');
    }

    /**
     * Search por categoria funciona
     */
    public function test_090_busca_por_categoria()
    {
        Category::create([
            'name' => 'Streaming',
            'slug' => 'streaming',
            'icon' => 'tv',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Category::create([
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'icon' => 'play',
            'color' => '#00ff00',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('search', 'Entertainment')
            ->assertSee('Entertainment');
    }

    /**
     * Search com XSS payload escapado
     */
    public function test_091_busca_com_payload_xss_escapada()
    {
        Category::create([
            'name' => 'Streaming',
            'slug' => 'streaming',
            'icon' => 'tv',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('search', '<img src=x onerror=alert(1)>')
            ->assertSee('Nenhuma categoria encontrada');
    }

    /**
     * Pagination: nextPage funciona
     */
    public function test_092_paginacao_proxima_pagina()
    {
        for ($i = 0; $i < 15; $i++) {
            Category::create([
                'name' => "Cat $i",
                'slug' => "cat-$i",
                'icon' => 'tv',
                'color' => '#ff0000',
                'is_system' => true,
                'privacy_token' => null,
            ]);
        }

        $component = Livewire::actingAs($this->admin)
            ->test('admin.manage-categories');

        $this->assertEquals(1, $component->get('page'));
        $component->call('nextPage');
        $this->assertEquals(2, $component->get('page'));
    }

    /**
     * Pagination: previousPage em page 1 not decrements
     */
    public function test_093_paginacao_pagina_anterior_na_primeira_pagina()
    {
        Category::create([
            'name' => 'Streaming',
            'slug' => 'streaming',
            'icon' => 'tv',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('page', 1)
            ->call('previousPage')
            ->assertSet('page', 1);
    }

    /**
     * Reset fields limpa os campos
     */
    public function test_094_resetar_campos_limpa_entradas()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Streaming')
            ->set('icon', 'tv')
            ->set('color', '#ff0000')
            ->call('resetFields')
            ->assertSet('name', '')
            ->assertSet('icon', '')
            ->assertSet('color', '#10b981')
            ->assertSet('categoryId', null)
            ->assertSet('isEditing', false);
    }

    /**
     * Slug é gerado automaticamente (Str::slug)
     */
    public function test_095_slug_gerado_automaticamente()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Streaming & Entertainment!!')
            ->set('icon', 'tv')
            ->set('color', '#ff0000')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'name' => 'Streaming & Entertainment!!',
            'slug' => 'streaming-entertainment',
        ]);
    }

    /**
     * Nome com caracteres especiais e acentos
     */
    public function test_096_nome_com_acentos_e_caracteres_especiais()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.manage-categories')
            ->set('name', 'Educação & Cultura - São Paulo')
            ->set('icon', 'book')
            ->set('color', '#ff0000')
            ->call('save');

        $this->assertDatabaseHas('categories', [
            'name' => 'Educação & Cultura - São Paulo',
        ]);
    }

    /**
     * Icon com XSS payload escapado na renderização
     */
    public function test_097_icone_com_payload_xss_renderizado_com_seguranca()
    {
        Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'icon' => '<img src=x onerror=alert(1)>',
            'color' => '#ff0000',
            'is_system' => true,
            'privacy_token' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.categories'));

        // Icon é usado como class name (bi bi-{icon}), então XSS é mitigado por design
        $this->assertStringContainsString('bi-&lt;img src=x onerror=alert(1)&gt;', $response->getContent());
    }
}
