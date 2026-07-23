package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.util.List;

@JsonIgnoreProperties(ignoreUnknown = true)
public class StudentProgressDto {
    @JsonProperty("post_count")        public int postCount;
    @JsonProperty("reply_count")       public int replyCount;
    @JsonProperty("participation_pct") public double participationPct;
    @JsonProperty("activity_by_day")   public List<ActivityDay> activityByDay;
    @JsonProperty("assessment_history") public List<AssessmentItem> assessmentHistory;
    @JsonProperty("latest_remark")     public LatestRemark latestRemark;

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class ActivityDay {
        public String label;
        public int count;
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class AssessmentItem {
        public String title;
        @JsonProperty("submitted_at_human") public String submittedAtHuman;
        @JsonProperty("score_pct")          public double scorePct;
        @JsonProperty("vs_peer_pct")        public Double vsPeerPct; // nullable
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class LatestRemark {
        public String criteria;
        public double score;
        @JsonProperty("created_at_human") public String createdAtHuman;
    }
}