<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Jobs\PostComment;
use App\Jobs\IncrementViewCount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ArticleController extends BaseController
{
    private Article $article;
    private Comment $comment;

    public function __construct(){
        $this->article = new Article();
        $this->comment = new Comment();
    }
    /**
     * @OA\Get(
     * path="/api/v1/articles",
     * summary="Articles",
     * description="Display list a paginated list of all articles",
     * operationId="Articles",
     * tags={"Articles"},

     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *        @OA\Property(property="data", type="object",
     *            @OA\Property(property="success", type="boolean", example="true"),
     *            @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                     @OA\Property(property="id", type="string", example="1"),
     *                     @OA\Property(property="title", type="string", example="Iure voluptatem et optio sint iusto architecto et dicta."),
     *                     @OA\Property(property="article_cover_thumbnail", type="string", example="https://via.placeholder.com/150/09f/fff.png"),
     *                      @OA\Property(property="description", type="string", example="Culpa facere laborum asperiores reiciendis. Optio et fugit perferendis aut placeat. Exercitationem e"),
     *              ),
     *              @OA\Items(type="object",
     *                     @OA\Property(property="slug", type="string", example="2"),
     *                     @OA\Property(property="description", type="string", example="Voluptates neque ut sint."),
     *                     @OA\Property(property="name", type="string", example="https://via.placeholder.com/150/09f/fff.png"),
     *                     @OA\Property(property="description", type="string", example="Expedita aut perferendis omnis nihil eum. Totam deserunt eum quam omnis voluptatibus tempora. Numqua"),
     *              ),
     *           @OA\Property(property="message", type="string", example="Articles retrieved successfully."),
     *          ),),
     *     )
     *  )
     * )
     */
    public function getArticles(Request $request): JsonResponse
    {
        //paginate articles
        $articles = $this->article::select(['id', 'title',  'cover_image_thumbnail_url as article_cover_thumbnail'])
            ->selectRaw('SUBSTRING(`body`, 1, 100) as `description`')
            ->orderByDesc('id')
            ->paginate(10);
        return $this->sendResponse($articles, 'Articles retrieved successfully.');
    }

    /**
     * @OA\Get(
     *      path="/api/v1/article/{id}",
     *      summary="Article Details",
     *      description="Display Details of an article",
     *     operationId="Article Details",
     *      @OA\Parameter(
     *          name="id",
     *          description="Article ID",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      tags={"Articles"},
     *     @OA\Response(
     *           response=401,
     *           description="Returns when article is not found",
     *           @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Article not found.")
     *           )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *              @OA\Property(property="success", type="boolean", example="true"),
     *               @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="string", example="50"),
     *                 @OA\Property(property="title", type="string", example="Iure voluptatem et optio sint iusto architecto et dicta"),
     *                  @OA\Property(property="cover_image_url", type="string", example="https://via.placeholder.com/150/09f/fff.png"),
     *             @OA\Property(property="body", type="string", example="Culpa facere laborum asperiores reiciendis. Optio et fugit perferendis aut placeat. Exercitationem expedita ipsam placeat quia eaque quaerat iure aut."),
     *              @OA\Property(property="cover_image_thumbnail_url", type="string", example="https://via.placeholder.com/150/09f/fff.png"),
     *              @OA\Property(property="views_count", type="string", example="1"),
     *                 @OA\Property(property="likes_count", type="string", example="1"),
     *     @OA\Property(property="comments", type="array",
     *                      @OA\Items(type="object",
     *                          @OA\Property(property="subject", type="string", example="hjfjt745gjfg"),
     *                          @OA\Property(property="comment", type="string", example="iksjwkwswims"),
     *                     @OA\Property(property="created_at", type="string", example="2022-01-01 00:00:00"),
     *                      ),
     *                  ),
     *      @OA\Property(property="tags", type="array",
     *                      @OA\Items(type="object",
     *                          @OA\Property(type="string", example="technology"),
     *                      ),
     *                  ),
     *
     *              ),
     *             @OA\Property(property="message", type="string", example="Article data retrieved successfully."),
     *         ),
     *     )
     *  )
     * )
     */
    public function getArticle(Request $request, $id): JsonResponse
    {
        //get article
        $article = $this->article::with('comments', 'tagsList.tag', 'publisher')->find($id);
        if (!$article) return $this->sendError('Article not found.');
        //get comments
        $comments = [];
        foreach ($article->comments as $comment) {
            $comments[] = [
                'subject' => $comment->subject,
                'comment' => $comment->body,
                'created_at' => $comment->created_at,
            ];
        }
        //get tags
        $tags = [];
        foreach ($article->tagsList->where('status', 'active') as $tagList) {
            $tags[] = $tagList->tag->name;
        }
        $data = [
            'id' => $article->id,
            'title' => $article->title,
            'body' => $article->body,
            'cover_image_url' => $article->cover_image_url,
            'cover_image_thumbnail_url' => $article->cover_image_thumbnail_url,
            'views_count' => $article->views_count,
            'likes_count' => $article->likes_count,
            'publisher' => $article->publisher ? $article->publisher->name : '',
            'created_date' => $article->created_at,
            'comments' => $comments,
            'tags' => $tags,

        ];
        //dispatch job to increment views count
        IncrementViewCount::dispatch($article->id)->delay(now()->addSeconds(5));

        //log view count

        return $this->sendResponse($data, 'Article data retrieved successfully.');

    }
    /**
     * @OA\Post(
     *      path="/api/v1/article/1/comment",
     *      summary="Comment on an article",
     *      description="comment on an article",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Comment on an article",
     *          @OA\JsonContent(
     *              required={"body","subject"},
     *              @OA\Property(property="subject", type="string", example="jsljslkls"),
     *              @OA\Property(property="body", type="text", example="Culpa facere laborum asperiores reiciendis. Optio et fugit perferendis aut placeat. Exercitationem expedita ipsam placeat quia eaque quaerat iure aut."),
     *          ),
     *      ),
     *      tags={"Articles"},
     *     operationId="Comment on an article",
     *      @OA\Response(
     *           response=401,
     *           description="Returns parameters are not vaid",
     *           @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="subject is required")
     *           )
     *      ),
     *      @OA\Response(
     *           response=400,
     *           description="Returns if article is not found",
     *           @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Article not found"),
     *              @OA\Property(property="success", type="boolean", example="false"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="error", type="array",
     *                      @OA\Items( type="string", example={"Article not found"})
     *                  )
     *              )
     *          )
     *
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *              @OA\Property(property="success", type="boolean", example="true"),
     *              @OA\Property(property="data", type="object",
     *                     @OA\Property(property="subject", type="string", example="jsljslkls"),
     *     @OA\Property(property="comment", type="string", example="Culpa facere laborum asperiores reiciendis. Optio et fugit perferendis aut placeat. Exercitationem expedita ipsam placeat quia eaque quaerat iure aut."),
     *     @OA\Property(property="created_at", type="string", example="2022-10-23T01:23:34.211613Z"),
     *             ),
     *             @OA\Property(property="message", type="string", example="Your comment has been successfully sent"),
     *         ),
     *     )
     *  )
     * )
     */
    public function postComment(Request $request, $id): JsonResponse
    {
        //validate comment
        $validator = Validator::make($request->all(), [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:255'],
        ]);
        if ($validator->fails()) return $this->sendError('Validation Error.', $validator->messages());
        $validated = $validator->validated();


        //get article
        $article = $this->article::find($id);
        if (!$article) return $this->sendError('Article not found.');


        //dispatch job to post comment
        //$this->dispatch(new PostComment($article->id, $validated['subject'], $validated['body']))->delay(now()->addSeconds(10));
        PostComment::dispatch( $article->id, $validated['subject'], $validated['body'])->delay(now()->addSeconds(10));
        $data = [
            'subject' => $validated['subject'],
            'comment' => $validated['body'],
            'created_at' => now(),
        ];


        return $this->sendResponse($data, 'Your comment has been successfully sent');
    }
    /**
     * @OA\Post(
     *      path="/api/v1/article/1/like",
     *      summary="Like an article",
     *      description="Like an article",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Like an article",
     *
     *      ),
     *      tags={"Articles"},
     *     operationId="Like an article",
     *
     *      @OA\Response(
     *           response=400,
     *           description="Returns if article is not found",
     *           @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Article not found"),
     *              @OA\Property(property="success", type="boolean", example="false"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="error", type="array",
     *                      @OA\Items( type="string", example={"Article not found"})
     *                  )
     *              )
     *          )
     *
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="object",
     *              @OA\Property(property="success", type="boolean", example="true"),
     *              @OA\Property(property="data", type="object",
     *                     @OA\Property(property="likes_count", type="integer", example=4),
     *             ),
     *             @OA\Property(property="message", type="string", example="Your comment has been successfully sent"),
     *         ),
     *     )
     *  )
     * )
     */
    public function likeArticle(Request $request, $id): JsonResponse
    {
        //get article
        try {
            DB::beginTransaction();
        $article = $this->article::lockForUpdate()->find($id);
        if (!$article){
            DB::rollBack();
            return $this->sendError('Article not found.');
        }
        //increment likes count
        $article->likes_count = $article->likes_count + 1;
        $article->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError('Error, please try again.');
        }
        $data = [
            'likes_count' => $article->likes_count,
        ];
        return $this->sendResponse($data, 'Article liked successfully.');
    }




}
