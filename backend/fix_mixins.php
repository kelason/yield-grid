<?php

$resources = [
    'app/Chat/Resources/ChatAttachmentResource.php' => 'App\Domain\Chat\Models\ChatAttachment',
    'app/Chat/Resources/ChatConversationResource.php' => 'App\Domain\Chat\Models\ChatConversation',
    'app/Chat/Resources/ChatMessageResource.php' => 'App\Domain\Chat\Models\ChatMessage',
    'app/Community/Resources/ForumAttachmentResource.php' => 'App\Domain\Community\Models\ForumAttachment',
    'app/Community/Resources/ForumCategoryResource.php' => 'App\Domain\Community\Models\ForumCategory',
    'app/Community/Resources/ForumReplyResource.php' => 'App\Domain\Community\Models\ForumReply',
    'app/Community/Resources/ForumTagResource.php' => 'App\Domain\Community\Models\ForumTag',
    'app/Community/Resources/ForumThreadResource.php' => 'App\Domain\Community\Models\ForumThread',
];

foreach ($resources as $file => $model) {
    $content = file_get_contents($file);
    if (! str_contains($content, '@mixin')) {
        $content = preg_replace('/class (\w+) extends JsonResource/', "/**\n * @mixin \\$model\n */\nclass $1 extends JsonResource", $content);
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
