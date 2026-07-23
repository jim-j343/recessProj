package forum.database;

import forum.api.dto.PostDto;
import forum.models.Post;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.io.TempDir;

import java.io.File;
import java.util.List;

import static org.junit.jupiter.api.Assertions.*;

class PostDaoTest {

    private PostDao postDao;

    @BeforeEach
    void setUp(@TempDir File tempDir) {
        System.setProperty("aces.db", new File(tempDir, "test.db").getAbsolutePath());
        SQLiteConnection.initSchema();
        postDao = new PostDao();
    }

    @AfterEach
    void tearDown() {
        System.clearProperty("aces.db");
    }

    @Test
    void create_savesLocallyAsUnsyncedAndQueuesForSync() {
        Post p = postDao.create(1L, 42L, null, "Hello world");

        assertNotNull(p);
        assertTrue(p.getPostId() > 0);
        assertFalse(p.isSynced());
        assertEquals("Hello world", p.getContent());

        List<Post> posts = postDao.listByTopic(1L);
        assertEquals(1, posts.size());
        assertFalse(posts.get(0).isSynced());
    }

    @Test
    void listByTopic_ordersByCreatedAtThenPostId() {
        postDao.create(1L, 1L, null, "first");
        postDao.create(1L, 1L, null, "second");
        postDao.create(2L, 1L, null, "different topic");

        List<Post> posts = postDao.listByTopic(1L);
        assertEquals(2, posts.size());
        assertEquals("first", posts.get(0).getContent());
        assertEquals("second", posts.get(1).getContent());
    }

    @Test
    void upsertFromServer_insertsWhenNoExistingRowWithThatServerId() {
        PostDto dto = new PostDto();
        dto.post_id = 500L;
        dto.topic_id = 7L;
        dto.author_id = 3L;
        dto.content = "From server";
        dto.created_at = "2026-01-01T00:00:00Z";

        postDao.upsertFromServer(dto);

        List<Post> posts = postDao.listByTopic(7L);
        assertEquals(1, posts.size());
        assertEquals("From server", posts.get(0).getContent());
        assertTrue(posts.get(0).isSynced());
        assertEquals(500L, posts.get(0).getPostId());
    }

    @Test
    void upsertFromServer_updatesExistingRowWithSameServerId() {
        PostDto dto = new PostDto();
        dto.post_id = 500L;
        dto.topic_id = 7L;
        dto.author_id = 3L;
        dto.content = "Original";
        dto.created_at = "2026-01-01T00:00:00Z";
        postDao.upsertFromServer(dto);

        dto.content = "Edited content";
        postDao.upsertFromServer(dto);

        List<Post> posts = postDao.listByTopic(7L);
        assertEquals(1, posts.size(), "should update in place, not duplicate");
        assertEquals("Edited content", posts.get(0).getContent());
    }

    @Test
    void markSynced_recordsServerIdAndFlagsSynced() {
        Post local = postDao.create(1L, 1L, null, "queued reply");
        assertFalse(local.isSynced());

        postDao.markSynced(local.getPostId(), 999L);

        Post refreshed = postDao.findById(local.getPostId());
        assertNotNull(refreshed);
        assertTrue(refreshed.isSynced());
    }

    @Test
    void findById_returnsNullWhenMissing() {
        assertNull(postDao.findById(99999L));
    }
}