package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.util.List;

@JsonIgnoreProperties(ignoreUnknown = true)
public class AdminDashboardDto {
    @JsonProperty("total_members") public int totalMembers;
    @JsonProperty("active_today") public int activeToday;
    public int warned;
    public int blacklisted;
    public List<AdminMemberDto> members;
    @JsonProperty("group_settings") public List<GroupSetting> groupSettings;

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class GroupSetting {
        @JsonProperty("group_id") public long groupId;
        public String name;
        @JsonProperty("course_name") public String courseName;
        @JsonProperty("members_count") public int membersCount;
        @JsonProperty("inactivity_warning_days") public int inactivityWarningDays;
        @JsonProperty("blacklist_duration_days") public int blacklistDurationDays;
    }
}
