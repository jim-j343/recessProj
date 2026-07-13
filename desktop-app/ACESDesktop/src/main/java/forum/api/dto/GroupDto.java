package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public class GroupDto {
    @JsonProperty("group_id")     public long   groupId;
    @JsonProperty("name")         public String name;
    @JsonProperty("description")  public String description;
    @JsonProperty("admin_id")     public long   adminId;
    @JsonProperty("admin_name")   public String adminName;
    @JsonProperty("member_count") public int    memberCount;
    @JsonProperty("topics_count") public int    topicsCount;
    @JsonProperty("my_status")    public String myStatus;  // null, "pending", "active"
    @JsonProperty("my_role")      public String myRole;    // null, "member", "moderator", "admin"
}