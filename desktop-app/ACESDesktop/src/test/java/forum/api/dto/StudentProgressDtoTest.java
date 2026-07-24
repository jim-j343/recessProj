package forum.api.dto;

import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class StudentProgressDtoTest {

    private final ObjectMapper mapper = new ObjectMapper();

    @Test
    void deserializesFullProgressShape() throws Exception {
        String json = """
            {
              "post_count": 3,
              "reply_count": 2,
              "participation_pct": 20.0,
              "activity_by_day": [
                {"label": "Mon", "count": 0},
                {"label": "Tue", "count": 2}
              ],
              "assessment_history": [
                {"title": "Midterm", "submitted_at_human": "15 Jul 2026", "score_pct": 80.0, "vs_peer_pct": 20.0}
              ],
              "latest_remark": {
                "criteria": "Great engagement this week",
                "score": 9,
                "created_at_human": "2 days ago"
              }
            }
            """;

        StudentProgressDto dto = mapper.readValue(json, StudentProgressDto.class);

        assertEquals(3, dto.postCount);
        assertEquals(2, dto.replyCount);
        assertEquals(20.0, dto.participationPct);

        assertEquals(2, dto.activityByDay.size());
        assertEquals("Tue", dto.activityByDay.get(1).label);
        assertEquals(2, dto.activityByDay.get(1).count);

        assertEquals(1, dto.assessmentHistory.size());
        assertEquals("Midterm", dto.assessmentHistory.get(0).title);
        assertEquals(20.0, dto.assessmentHistory.get(0).vsPeerPct);

        assertNotNull(dto.latestRemark);
        assertEquals("Great engagement this week", dto.latestRemark.criteria);
        assertEquals(9.0, dto.latestRemark.score);
    }

    @Test
    void vsPeerPctIsNullableWhenNoOtherSubmissionsExist() throws Exception {
        String json = """
            {
              "post_count": 0, "reply_count": 0, "participation_pct": 0,
              "activity_by_day": [],
              "assessment_history": [
                {"title": "Solo Quiz", "submitted_at_human": "1 Jan 2026", "score_pct": 100.0, "vs_peer_pct": null}
              ],
              "latest_remark": null
            }
            """;

        StudentProgressDto dto = mapper.readValue(json, StudentProgressDto.class);

        assertNull(dto.assessmentHistory.get(0).vsPeerPct);
        assertNull(dto.latestRemark);
    }
}