<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Project;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManageProjectsTest extends TestCase
{

    use WithFaker, RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
   
        
    public function test_guest_cannot_manage_projects(){
            
        // $this->withoutExceptionHandling();

        $project = factory('App\Project')->create(); 
        
        // Si tu essaie de post tu es redirigé
        $this->post('/projects', $project->toArray())->assertRedirect('login');
        
        // Si tu essaie d'aller sur la page /create tu es redirigé
        $this->get('/projects/create')->assertRedirect('login');

        // Si tu essaies d'acceder au dashboard tu es redirigé
        $this->get('/projects')->assertRedirect('login');
        
        // Si tu essaies d'acceder a un projet spécifique tu es redirigé
        $this->get($project->path())->assertRedirect('login');
    }
      
     public function test_a_user_can_create_a_project(){

        $this->withoutExceptionHandling();
        
        $this->signIn();

        // déplacé dans TestCase.php
        // $this->actingAs(factory('App\User')->create());

        $this->get('/projects/create')->assertStatus(200);

        $attributes = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->sentence,
            'notes' => 'general notes here'
        ];
        
        $response = $this->post('/projects', $attributes);
        
        $project = Project::where($attributes)->first();

        $response->assertRedirect($project->path());

        $this->assertDatabaseHas('projects', $attributes);
        
        $this->get($project->path())
        ->assertSee($attributes['title'])
        ->assertSee($attributes['description'])
        ->assertSee($attributes['notes']);
    }
    
    public function test_a_user_can_update_a_project(){
        
        $this->signIn();
        
        $this->withoutExceptionHandling();

        $project = factory('App\Project')->create(['owner_id' => auth()->id()]);

        $this->patch($project->path(), [
            'notes' => 'Changed'
        ])->assertRedirect($project->path());

        $this->assertDatabaseHas('projects', ['notes' => 'Changed']);
    }
        
    public function test_a_user_can_view_their_project(){

        $this->signIn();
        
        $this->withoutExceptionHandling();

        $project = factory('App\Project')->create(['owner_id' => auth()->id()]);

        $this->get($project->path())

            ->assertSee($project->title);

            // ->assertSee($project->description);
    }

    public function test_an_authenticated_user_cannot_view_the_projects_of_others(){
        
        $this->signIn();
        
        // $this->withoutExceptionHandling();

        //Comparé au test plus haut (& voir le factory project), son owner id ne correspond pas à l'id de l'user, donc ce projet ne lui appartient pas, on renvoi une 403
        $project = factory('App\Project')->create();

        $this->get($project->path())->assertStatus(403);
    }

    public function test_an_authenticated_user_cannot_update_the_projects_of_others(){
        
        $this->signIn();
        
        // $this->withoutExceptionHandling();
 
        $project = factory('App\Project')->create();

        $this->get($project->path(), [])->assertStatus(403);
    }

    public function test_a_project_requires_a_title(){
        
        $this->signIn();
        
        $attributes = factory('App\Project')->raw(['title' => '']);

        $this->post('/projects', $attributes)->assertSessionHasErrors('title');
    }

    public function test_a_project_requires_a_description(){
        
        $this->signIn();
        
        $attributes = factory('App\Project')->raw(['description' => '']);

        $this->post('/projects', $attributes)->assertSessionHasErrors('description');
    }
}