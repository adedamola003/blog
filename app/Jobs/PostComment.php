<?php

namespace App\Jobs;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PostComment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $articleId;
    public $subject;
    public $body;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($articleId, $subject, $body)
    {
        $this->articleId = $articleId;
        $this->subject = $subject;
        $this->body = $body;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $thisComment = new Comment();
        $thisComment->create([
            'article_id' => $this->articleId,
            'subject' => $this->subject,
            'body' => $this->body,
        ]);
    }
}
