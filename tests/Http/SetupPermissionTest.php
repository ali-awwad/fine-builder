<?php

namespace AliAwwad\FineBuilder\Tests\Http;

use AliAwwad\FineBuilder\Support\Paths;
use AliAwwad\FineBuilder\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\User;

class SetupPermissionTest extends TestCase
{
    /** @var \Statamic\Contracts\Auth\User[] */
    private array $users = [];

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Roles and more than one user need Pro.
        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('statamic.users.repositories.file.paths.roles', __DIR__.'/../__fixtures__/site/roles.yaml');
    }

    protected function tearDown(): void
    {
        foreach ($this->users as $user) {
            $user->delete();
        }

        parent::tearDown();
    }

    private function user(array $permissions): \Statamic\Contracts\Auth\User
    {
        $role = Role::make('role_'.count($this->users))->permissions(['access cp', ...$permissions]);
        $role->save();

        return $this->users[] = User::make()->email('user'.count($this->users).'@example.com')->assignRole($role)->save();
    }

    public static function routes(): array
    {
        return [
            'setup page' => ['get', 'fine-builder.index', []],
            'install' => ['post', 'fine-builder.install', ['force' => 1]],
            'add blocks' => ['post', 'fine-builder.blocks', ['blocks' => ['hero']]],
            'collections' => ['post', 'fine-builder.collections', ['collections' => ['pages']]],
        ];
    }

    #[Test]
    #[DataProvider('routes')]
    public function control_panel_users_without_the_permission_are_forbidden(string $method, string $route, array $data): void
    {
        Collection::make('pages')->save();
        File::put($fieldset = Paths::fieldsets('fine_builder.yaml'), 'title: Customised');

        $this->actingAs($this->user([]));

        // The Control Panel answers JSON requests with a 403 and sends browser requests back with an error.
        $this->{$method.'Json'}(cp_route($route), $data)->assertForbidden();
        $this->{$method}(cp_route($route), $data)->assertRedirect()->assertSessionHas('error');

        $this->assertSame('title: Customised', File::get($fieldset));
        $this->assertNull(Collection::find('pages')->entryBlueprint()?->field(config('fine-builder.field', 'fine_builder')));
    }

    #[Test]
    public function users_with_the_permission_can_use_the_setup_page(): void
    {
        Collection::make('pages')->save();

        $this->actingAs($this->user(['configure fine builder']));

        $this->get(cp_route('fine-builder.index'))->assertOk();
        $this->post(cp_route('fine-builder.collections'), ['collections' => ['pages']])
            ->assertRedirect(cp_route('fine-builder.index'))
            ->assertSessionMissing('error');

        $this->assertNotNull(Collection::find('pages')->entryBlueprint()->field(config('fine-builder.field', 'fine_builder')));
    }
}
