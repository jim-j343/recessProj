<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TopicExportPdfTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'student'): User
    {
        return User::create([
            'username'      => 'user_'.uniqid(),
            'email'         => uniqid().'@test.com',
            'password_hash' => bcrypt('password'),
            'system_role'   => $role,
            'status'        => 'active',
        ]);
    }

    private function joinGroup(User $user, Group $group): void
    {
        GroupMembership::create([
            'user_id'   => $user->user_id,
            'group_id'  => $group->group_id,
            'role'      => 'member',
            'status'    => 'active',
            'joined_at' => now(),
        ]);
    }

    public function test_non_admin_pdf_export_hides_excluded_posts_and_logs_engagement(): void
    {
        $author = $this->makeUser();
        $viewer = $this->makeUser();
        $group = Group::create(['admin_id' => $author->user_id, 'name' => 'Algorithms', 'course_name' => 'CS101']);

        $this->joinGroup($author, $group);
        $this->joinGroup($viewer, $group);

        $topic = Topic::create([
            'group_id'   => $group->group_id,
            'creator_id' => $author->user_id,
            'title'      => 'Sorting algorithms comparison',
            'category'   => 'Algorithms',
        ]);

        $openingPost = Post::create([
            'topic_id'  => $topic->topic_id,
            'author_id' => $author->user_id,
            'content'   => 'Opening post',
            'is_synced' => true,
        ]);

        $excludedReply = Post::create([
            'topic_id'  => $topic->topic_id,
            'author_id' => $author->user_id,
            'content'   => 'Private follow-up',
            'is_synced' => true,
        ]);
        $excludedReply->excludedUsers()->attach($viewer->user_id);

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('download')
            ->once()
            ->andReturn(response('fake-pdf', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$topic->title.'.pdf"',
            ]));

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use ($topic, $openingPost) {
                return $view === 'exports.topic-pdf'
                    && $data['topic']->topic_id === $topic->topic_id
                    && $data['firstPost']->post_id === $openingPost->post_id
                    && $data['firstPost']->content === 'Opening post'
                    && $data['replies']->isEmpty();
            })
            ->andReturn($pdf);

        $response = $this->actingAs($viewer)->get('/topics/'.$topic->topic_id.'/export-pdf');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('user_engagements', [
            'user_id'         => $viewer->user_id,
            'topic_id'        => $topic->topic_id,
            'engagement_type' => 'export',
        ]);
    }
}
