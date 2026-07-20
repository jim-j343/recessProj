package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.util.List;

@JsonIgnoreProperties(ignoreUnknown = true)
public class StudentDashboardDto {
    @JsonProperty("participation_avg") public double participationAvg;
    @JsonProperty("participation_by_group") public List<ParticipationByGroup> participationByGroup;
    public Standing standing;
    @JsonProperty("latest_topic") public TopicSummary latestTopic;
    @JsonProperty("recommended_topic") public TopicSummary recommendedTopic;

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class ParticipationByGroup {
        @JsonProperty("group_name") public String groupName;
        public double pct;
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class Standing {
        public String status; // "good" | "warning"
        @JsonProperty("warning_number") public Integer warningNumber;
        public String label;
        public String sub;
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class TopicSummary {
        @JsonProperty("topic_id") public long topicId;
        public String title;
        @JsonProperty("group_name") public String groupName;
        @JsonProperty("posts_count") public int postsCount;
        @JsonProperty("created_at_human") public String createdAtHuman;
    }
}