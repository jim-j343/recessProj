package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.util.List;

@JsonIgnoreProperties(ignoreUnknown = true)
public class AdminAnalyticsDto {
    @JsonProperty("total_members") public int totalMembers;
    @JsonProperty("active_this_week") public int activeThisWeek;
    @JsonProperty("warnings_this_week") public int warningsThisWeek;
    @JsonProperty("active_blacklists") public int activeBlacklists;
    @JsonProperty("post_volume") public List<CountPoint> postVolume;
    @JsonProperty("group_performance") public List<GroupPerformance> groupPerformance;
    @JsonProperty("group_activity") public List<CountPoint> groupActivity;
    public List<GroupSummary> groups;
    @JsonProperty("recent_activity") public List<ActivityItem> recentActivity;

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class CountPoint {
        public String label;
        public String name;
        public int count;
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class GroupPerformance {
        public String name;
        @JsonProperty("avg_pct") public Double avgPct;
        public int count;
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class GroupSummary {
        @JsonProperty("group_id") public long groupId;
        public String name;
        @JsonProperty("topics_count") public int topicsCount;
        @JsonProperty("members_count") public int membersCount;
    }

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class ActivityItem {
        public String user;
        public String action;
        public String group;
        @JsonProperty("logged_at_human") public String loggedAtHuman;
    }
}
