package forum.api.dto;

import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class PostDtoTest {

    private final ObjectMapper mapper = new ObjectMapper();

    @Test
    void deserializesOrdinaryPostWithoutExclusions() throws Exception {
        String json = """
            {
              "post_id": 10,
              "topic_id": 3,
              "author_id": 7,
              "parent_post_id": null,
              "content": "Hello",
              "created_at": "2026-01-01T00:00:00Z",
              "author": "mugisha_dan"
            }
            """;

        PostDto dto = mapper.readValue(json, PostDto.class);

        assertEquals(10L, dto.post_id);
        assertEquals("Hello", dto.content);
        assertEquals("mugisha_dan", dto.author);
        assertNull(dto.parent_post_id);
        assertNull(dto.excluded_usernames, "field is absent from JSON when there are no exclusions");
    }

    @Test
    void deserializesExcludedUsernamesWhenPresent_authorViewOnly() throws Exception {
        String json = """
            {
              "post_id": 11,
              "topic_id": 3,
              "author_id": 7,
              "content": "Hidden from someone",
              "created_at": "2026-01-01T00:00:00Z",
              "author": "mugisha_dan",
              "excluded_usernames": ["kayongo_moses", "atim_grace"]
            }
            """;

        PostDto dto = mapper.readValue(json, PostDto.class);

        assertNotNull(dto.excluded_usernames);
        assertEquals(2, dto.excluded_usernames.size());
        assertTrue(dto.excluded_usernames.contains("kayongo_moses"));
    }

    @Test
    void parentPostIdRoundTripsAsNullableLong() throws Exception {
        String json = """
            {"post_id": 12, "topic_id": 3, "author_id": 7, "parent_post_id": 4, "content": "Reply to 4"}
            """;

        PostDto dto = mapper.readValue(json, PostDto.class);

        assertEquals(4L, dto.parent_post_id);
    }
}