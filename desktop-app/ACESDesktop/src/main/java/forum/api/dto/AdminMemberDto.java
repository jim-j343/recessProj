package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public class AdminMemberDto {
    @JsonProperty("user_id") public long userId;
    public String username;
    public String email;
    @JsonProperty("system_role") public String systemRole;
    public String status;
    @JsonProperty("last_active_human") public String lastActiveHuman;
    @JsonProperty("posts_count") public int postsCount;
    @JsonProperty("unheeded_warning_count") public int unheededWarningCount;
    @JsonProperty("latest_warning_number") public Integer latestWarningNumber;
    @JsonProperty("active_blacklist") public ActiveBlacklist activeBlacklist;

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class ActiveBlacklist {
        public String reason;
        @JsonProperty("days_remaining") public Integer daysRemaining;
    }
}
