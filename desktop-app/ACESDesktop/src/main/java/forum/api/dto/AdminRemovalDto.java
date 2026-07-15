package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonProperty;

public class AdminRemovalDto {
    public long id;
    
    @JsonProperty("group_name")
    public String groupName;
    
    @JsonProperty("removed_user")
    public String removedUser;
    
    @JsonProperty("removed_by")
    public String removedBy;
    
    public String reason;
    
    public boolean reviewed;
    
    @JsonProperty("reviewed_by")
    public String reviewedBy;
    
    @JsonProperty("created_at")
    public String createdAt;
    
    @JsonProperty("created_at_human")
    public String createdAtHuman;
}
