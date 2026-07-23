package forum.api.dto;

import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class TopicDetailResponseTest {

    private final ObjectMapper mapper = new ObjectMapper();

    @Test
    void deserializesTopicPostsAndGroupRoster() throws Exception {
        String json = """
            {
              "topic": {
                "topic_id": 1, "group_id": 2, "creator_id": 3,
                "title": "How do foreign keys work in MySQL?",
                "category": "Database", "created_at": "2026-07-15T13:59:00Z",
                "author": "mugisha_dan", "replies": 6
              },
              "posts": [
                {"post_id": 100, "topic_id": 1, "author_id": 3, "content": "Opening post", "author": "mugisha_dan"}
              ],
              "group_members": [
                {"user_id": 5, "username": "kayongo_moses", "status": "active", "role": "member"},
                {"user_id": 6, "username": "atim_grace", "status": "active", "role": "member"}
              ]
            }
            """;

        TopicDetailResponse response = mapper.readValue(json, TopicDetailResponse.class);

        assertNotNull(response.topic);
        assertEquals("How do foreign keys work in MySQL?", response.topic.title);

        assertEquals(1, response.posts.size());
        assertEquals("Opening post", response.posts.get(0).content);

        assertEquals(2, response.groupMembers.size());
        assertEquals("kayongo_moses", response.groupMembers.get(0).username);
        assertEquals(5L, response.groupMembers.get(0).userId);
    }

    @Test
    void groupMembersAbsentFromJsonDeserializesToNull() throws Exception {
        String json = """
            {
              "topic": {"topic_id": 1, "group_id": 2, "creator_id": 3, "title": "T"},
              "posts": []
            }
            """;

        TopicDetailResponse response = mapper.readValue(json, TopicDetailResponse.class);

        assertNull(response.groupMembers);
        assertTrue(response.posts.isEmpty());
    }
}