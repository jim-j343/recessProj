package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public class MemberDto {
    @JsonProperty("user_id")  public long   userId;
    @JsonProperty("username") public String username;
    @JsonProperty("status")   public String status;
    @JsonProperty("role")     public String role;
}