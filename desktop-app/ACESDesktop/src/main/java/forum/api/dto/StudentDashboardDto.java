package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public class StudentDashboardDto {
    @JsonProperty("participation_pct") public double participationPct;
    public String standing; // "active" | "warned" | "blacklisted"
    @JsonProperty("latest_warning")    public LatestWarning latestWarning;   // nullable
    @JsonProperty("latest_topic")      public TopicSummary  latestTopic;     // nullable
    @JsonProperty("recommended_topic") public TopicSummary  recommendedTopic; // nullable

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class LatestWarning {
        @JsonProperty("warning_number") public int    warningNumber;
        @JsonProperty("deadline_human") public String deadlineHuman;
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class TopicSummary {
        @JsonProperty("topic_id")         public long   topicId;
        public String title;
        @JsonProperty("group_name")       public String groupName;
        @JsonProperty("posts_count")      public int    postsCount;
        @JsonProperty("created_at_human") public String createdAtHuman;
    }
}