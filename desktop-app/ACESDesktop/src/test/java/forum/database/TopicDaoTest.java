package forum.database;

import forum.api.dto.TopicDto;
import forum.models.Topic;
import org.junit.jupiter.api.AfterEach;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.io.TempDir;

import java.io.File;

import static org.junit.jupiter.api.Assertions.*;

class TopicDaoTest {

    private TopicDao topicDao;

    @BeforeEach
    void setUp(@TempDir File tempDir) {
        System.setProperty("aces.db", new File(tempDir, "test.db").getAbsolutePath());
        SQLiteConnection.initSchema();
        topicDao = new TopicDao();
    }

    @AfterEach
    void tearDown() {
        System.clearProperty("aces.db");
    }

    @Test
    void create_savesLocallyAndIsFindableById() {
        Topic t = topicDao.create(1L, 2L, "New Topic", "Database");

        assertNotNull(t);
        assertTrue(t.getTopicId() > 0);
        assertEquals("New Topic", t.getTitle());

        Topic found = topicDao.findById(t.getTopicId());
        assertNotNull(found);
        assertEquals("New Topic", found.getTitle());
        assertEquals("Database", found.getCategory());
    }

    @Test
    void serverIdFor_isZeroForANeverSyncedLocalTopic() {
        Topic t = topicDao.create(1L, 2L, "Local only", null);
        assertEquals(0, topicDao.serverIdFor(t.getTopicId()));
    }

    @Test
    void upsertFromServer_insertsNewTopicKeyedByServerId() {
        TopicDto dto = new TopicDto();
        dto.topic_id = 42L;
        dto.group_id = 1L;
        dto.creator_id = 2L;
        dto.title = "From Server";
        dto.category = "General";
        dto.created_at = "2026-01-01T00:00:00Z";
        dto.posts_count = 5;

        topicDao.upsertFromServer(dto);

        Topic found = topicDao.findById(42L);
        assertNotNull(found);
        assertEquals("From Server", found.getTitle());
    }

    @Test
    void upsertFromServer_updatesExistingRowWithSameServerId() {
        TopicDto dto = new TopicDto();
        dto.topic_id = 42L;
        dto.group_id = 1L;
        dto.creator_id = 2L;
        dto.title = "Original Title";
        dto.category = "General";
        dto.created_at = "2026-01-01T00:00:00Z";
        topicDao.upsertFromServer(dto);

        dto.title = "Edited Title";
        topicDao.upsertFromServer(dto);

        Topic found = topicDao.findById(42L);
        assertEquals("Edited Title", found.getTitle());
    }

    @Test
    void setServerId_isReflectedByServerIdFor() {
        Topic t = topicDao.create(1L, 2L, "Local Topic", null);
        assertEquals(0, topicDao.serverIdFor(t.getTopicId()));

        topicDao.setServerId(t.getTopicId(), 777L);

        assertEquals(777L, topicDao.serverIdFor(t.getTopicId()));
    }

    @Test
    void deleteLocal_removesTopicAndItsPosts() {
        Topic t = topicDao.create(1L, 2L, "To be deleted", null);
        PostDao postDao = new PostDao();
        postDao.create(t.getTopicId(), 2L, null, "a reply");

        topicDao.deleteLocal(t.getTopicId());

        assertNull(topicDao.findById(t.getTopicId()));
        assertTrue(postDao.listByTopic(t.getTopicId()).isEmpty());
    }

    @Test
    void updateLocal_changesTitleAndCategory() {
        Topic t = topicDao.create(1L, 2L, "Old Title", "Old Category");

        topicDao.updateLocal(t.getTopicId(), "New Title", "New Category");

        Topic found = topicDao.findById(t.getTopicId());
        assertEquals("New Title", found.getTitle());
        assertEquals("New Category", found.getCategory());
    }

    @Test
    void firstPostContent_returnsEarliestPostByPostId() {
        Topic t = topicDao.create(1L, 2L, "Topic", null);
        PostDao postDao = new PostDao();
        postDao.create(t.getTopicId(), 2L, null, "first content");
        postDao.create(t.getTopicId(), 2L, null, "second content");

        assertEquals("first content", topicDao.firstPostContent(t.getTopicId()));
    }
}