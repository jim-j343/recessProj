package forum.services;

import forum.api.ApiClient;
import forum.api.ApiException;
import forum.api.dto.PostDto;
import forum.api.dto.TopicDto;
import forum.app.Session;
import forum.database.LocalCacheDAO;
import forum.database.LocalCacheDAO.PendingSync;
import forum.database.PostDao;
import forum.database.TopicDao;
import forum.models.Post;
import forum.models.Topic;

import java.io.IOException;
import java.util.List;

/**
 * Offline-first sync engine, driven by the {@code sync_log} queue.
 *
 *   push — upload locally-created topics/posts (status 'pending'); on success
 *          record the returned server id and mark the row 'synced'.
 *   pull — fetch the server's topics and upsert them into the local cache.
 *
 * A missing token means we're not authenticated online (offline), so sync is
 * a no-op. Safe to run on a background thread.
 */
public class SyncService {

    private final ApiClient api = new ApiClient();
    private final LocalCacheDAO cache = new LocalCacheDAO();
    private final TopicDao topicDao = new TopicDao();
    private final PostDao postDao = new PostDao();

    /** Outcome of a sync pass. */
    public record SyncResult(boolean online, int pushed, int pulled) {
        static SyncResult offline() { return new SyncResult(false, 0, 0); }
    }

    public synchronized SyncResult syncNow() {
        String token = Session.authToken();
        if (token == null || token.isBlank()) {
            System.out.println("[ACES] SyncService: no token — offline, skipping sync.");
            return SyncResult.offline();
        }
        int pushed = push(token);
        int pulled = pull(token);
        System.out.printf("[ACES] SyncService: pushed %d, pulled %d.%n", pushed, pulled);
        return new SyncResult(true, pushed, pulled);
    }

    private int push(String token) {
        int pushed = 0;
        for (PendingSync row : cache.pending()) {
            try {
                if ("topics".equals(row.table())) {
                    Topic t = topicDao.findById(row.localId());
                    if (t == null) continue;
                    String content = topicDao.firstPostContent(row.localId());
                    if (content == null || content.isBlank()) content = t.getTitle();
                    TopicDto created = api.createTopic(token, t.getGroupId(),
                            t.getTitle(), t.getCategory(), content);
                    topicDao.setServerId(row.localId(), created.topic_id);
                    cache.markSynced(row.syncId(), created.topic_id);
                    pushed++;
                } else if ("posts".equals(row.table())) {
                    Post p = postDao.findById(row.localId());
                    if (p == null) continue;
                    long serverTopicId = topicDao.serverIdFor(p.getTopicId());
                    if (serverTopicId <= 0) continue;   // parent topic not synced yet — retry later
                    PostDto created = api.createPost(token, serverTopicId, p.getContent(), null, null);
                    postDao.markSynced(row.localId(), created.post_id);
                    cache.markSynced(row.syncId(), created.post_id);
                    pushed++;
                }
            } catch (ApiException rejected) {
                System.out.println("[ACES] SyncService: server rejected a queued row — " + rejected.getMessage());
                // leave the row pending; a later fix/retry can resolve it
            } catch (IOException | InterruptedException offline) {
                if (offline instanceof InterruptedException) Thread.currentThread().interrupt();
                break;   // went offline mid-push; stop and try again next time
            }
        }
        return pushed;
    }

    private int pull(String token) {
        try {
            List<TopicDto> topics = api.listTopics(token);
            for (TopicDto t : topics) topicDao.upsertFromServer(t);
            return topics.size();
        } catch (ApiException | IOException | InterruptedException e) {
            if (e instanceof InterruptedException) Thread.currentThread().interrupt();
            return 0;
        }
    }
}