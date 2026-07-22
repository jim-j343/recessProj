package forum.api.dto;

import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class StudentDashboardDtoTest {

    private final ObjectMapper mapper = new ObjectMapper();

    @Test
    void deserializesFullShapeFromRealApiResponse() throws Exception {
        String json = """
            {
              "participation_avg": 60.0,
              "participation_by_group": [
                {"group_name": "Group A", "pct": 20},
                {"group_name": "Group B", "pct": 100}
              ],
              "standing": {
                "status": "warning",
                "warning_number": 1,
                "label": "Warning #1",
                "sub": "Comply before 01 Aug 2026"
              },
              "latest_topic": {
                "topic_id": 5,
                "title": "Latest Topic",
                "group_name": "Group A",
                "posts_count": 3,
                "created_at_human": "2 hours ago"
              },
              "recommended_topic": null
            }
            """;

        StudentDashboardDto dto = mapper.readValue(json, StudentDashboardDto.class);

        assertEquals(60.0, dto.participationAvg);
        assertEquals(2, dto.participationByGroup.size());
        assertEquals("Group A", dto.participationByGroup.get(0).groupName);
        assertEquals(20, dto.participationByGroup.get(0).pct);

        assertEquals("warning", dto.standing.status);
        assertEquals(1, dto.standing.warningNumber);
        assertEquals("Warning #1", dto.standing.label);

        assertNotNull(dto.latestTopic);
        assertEquals(5L, dto.latestTopic.topicId);
        assertEquals("Latest Topic", dto.latestTopic.title);
        assertEquals(3, dto.latestTopic.postsCount);

        assertNull(dto.recommendedTopic);
    }

    @Test
    void goodStandingHasNullWarningNumber() throws Exception {
        String json = """
            {
              "participation_avg": 0,
              "participation_by_group": [],
              "standing": {"status": "good", "warning_number": null, "label": "Good Standing", "sub": "No active warnings"},
              "latest_topic": null,
              "recommended_topic": null
            }
            """;

        StudentDashboardDto dto = mapper.readValue(json, StudentDashboardDto.class);

        assertEquals("good", dto.standing.status);
        assertNull(dto.standing.warningNumber);
    }

    @Test
    void ignoresUnknownFieldsWithoutThrowing() throws Exception {
        String json = """
            {
              "participation_avg": 10,
              "participation_by_group": [],
              "standing": {"status": "good", "label": "Good Standing", "sub": "ok", "some_future_field": true},
              "latest_topic": null,
              "recommended_topic": null,
              "a_totally_new_top_level_field": "should not break deserialization"
            }
            """;

        assertDoesNotThrow(() -> mapper.readValue(json, StudentDashboardDto.class));
    }
}