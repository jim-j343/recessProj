<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $topic->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 6px 0;
        }
        .meta {
            font-size: 10px;
            color: #666;
        }
        .category-badge {
            display: inline-block;
            background: #f0f0f0;
            color: #444;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            margin-top: 6px;
        }
        .original-post {
            background: #f7f7fb;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 14px;
            margin-bottom: 22px;
        }
        .original-post .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b5ecf;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .post-author {
            font-weight: bold;
            font-size: 11px;
        }
        .post-time {
            font-size: 9px;
            color: #888;
            margin-left: 6px;
        }
        .post-content {
            margin-top: 4px;
            font-size: 11px;
        }
        .replies-heading {
            font-size: 13px;
            font-weight: bold;
            margin: 18px 0 10px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }
        .reply {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .footer {
            margin-top: 30px;
            font-size: 9px;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <p class="title">{{ $topic->title }}</p>
        <p class="meta">
            Started by {{ $topic->creator->username ?? 'Unknown' }}
            on {{ $topic->created_at->format('d M Y, H:i') }}
        </p>
        @if($topic->category)
            <span class="category-badge">{{ $topic->category }}</span>
        @endif
    </div>

    @if($firstPost)
        <div class="original-post">
            <p class="label">Original Post</p>
            <span class="post-author">{{ $firstPost->author->username ?? 'Unknown' }}</span>
            <span class="post-time">{{ $firstPost->created_at->format('d M Y, H:i') }}</span>
            <p class="post-content">{{ $firstPost->content }}</p>
        </div>
    @endif

    <p class="replies-heading">Replies ({{ $replies->count() }})</p>

    @forelse($replies as $reply)
        <div class="reply">
            <span class="post-author">{{ $reply->author->username ?? 'Unknown' }}</span>
            <span class="post-time">{{ $reply->created_at->format('d M Y, H:i') }}</span>
            <p class="post-content">{{ $reply->content }}</p>
        </div>
    @empty
        <p style="color:#999; font-style: italic;">No replies yet.</p>
    @endforelse

    <div class="footer">
        Exported from Smart Discussion Forum on {{ now()->format('d M Y, H:i') }}
    </div>

</body>
</html>
