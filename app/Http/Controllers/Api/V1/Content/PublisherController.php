<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Article;
use App\Models\PostTag;
use App\Models\Tag;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PublisherController extends BaseController
{
    private Tag $tag;
    private Article $article;
    private PostTag $postTag;

    public function __construct(){
        $this->tag = new Tag();
        $this->article = new Article();
        $this->postTag = new PostTag();
    }

    /**
     * @OA\Get(
     * path="/api/v1/publisher/list_tags",
     * summary="Publishing",
     * description="Display list of all tas",
     * operationId="TagsList",
     * tags={"Publishing"},
     *    security={ {"bearer": {} }},
        *     @OA\Response(
     *           response=401,
     *           description="Unauthenticated",
     *           @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated")
     *           )
     *      ),
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *        @OA\Property(property="data", type="object",
     *            @OA\Property(property="success", type="boolean", example="true"),
     *            @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                     @OA\Property(property="name", type="string", example="fashion")
     *              ),
     *              @OA\Items(type="object",
     *                     @OA\Property(property="name", type="string", example="technology"),
     *              ),
     *           @OA\Property(property="message", type="string", example="Tags retrieved successfully."),
     *          ),),
     *     )
     *  )
     * )
     */
    public function getTags(): JsonResponse
    {
        $tags = $this->tag::select('id', 'name')->get();
        return response()->json(['success' => true, 'message' => 'Tags retrieved successfully.', 'data' => $tags]);
    }
    //

    /**
     * @throws ValidationException
     * @throws Exception
     */

    /**
     * @OA\Post(
     *      path="/api/v1/publisher/create",
     *      summary="Publish an article",
     *      description="Publish an article",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Comment on an article",
     *          @OA\JsonContent(
     *              required={"title","body","tags","cover_image_url", "cover_image_thumbnail_url"},
     *              @OA\Property(property="title", type="string", example="jsljslkls"),
     *              @OA\Property(property="body", type="text", example="Culpa facere laborum asperiores reiciendis. Optio et fugit perferendis aut placeat. Exercitationem expedita ipsam placeat quia eaque quaerat iure aut."),
     *     @OA\Property(property="tags", type="string", example="dolorem,fashion"),
     *     @OA\Property(property="cover_image_url", type="string", example="https://via.placeholder.com/350x150"),
     *     @OA\Property(property="cover_image_thumbnail_url", type="string", example="https://via.placeholder.com/150/09f/fff.png"),
     *          ),
     *      ),
     *      tags={"Publishing"},
     *     operationId="Publich article",
     *     security={ {"bearer": {} }},
     *      @OA\Response(
     *           response=401,
     *           description="Returns parameters are not vaid",
     *           @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="title is required")
     *           ),
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
     *                     @OA\Property(property="articleId", type="string", example="jsljslkls"),
     *             ),
     *             @OA\Property(property="message", type="string", example="Article published successfully."),
     *         ),
     *     )
     *  )
     * )
     */
    public function publishArticle(Request $request): JsonResponse
    {
        //validate comment
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'tags' => ['required', 'string', 'max:1000'],
            'cover_image_url' => ['required', 'url', 'max:255'],
            'cover_image_thumbnail_url' => ['required', 'url', 'max:255'],
        ]);
        if ($validator->fails()) return $this->sendError('Validation Error.', $validator->messages());
        $validated = $validator->validated();

        $tags = $validated['tags'];
        //separate string to array
        $tags = explode(',', $tags);
        $thisTags = $this->tag::whereIn('name', $tags)->get();
        $thisTags = $thisTags->pluck('id')->toArray();

        $article = $this->article::create([
            'title' => $validated['title'],
            'slug' => generateSlug(),
            'body' => $validated['body'],
            'cover_image_url' => $validated['cover_image_url'],
            'cover_image_thumbnail_url' => $validated['cover_image_thumbnail_url'],
            'user_id' => Auth::user()->id,
        ]);

        foreach($thisTags as $tag){
            $this->postTag::create([
                'article_id' => $article->id,
                'tag_id' => $tag,
            ]);
        }

        $data = [
            'articleId' => $article->id,
        ];
        return $this->sendResponse($data, 'Article published successfully.');
    }
}
