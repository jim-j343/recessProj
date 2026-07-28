package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

/** JSON shape of a user returned by the Laravel API (subset we use). */
@JsonIgnoreProperties(ignoreUnknown = true)
public class UserDto {
    public long   user_id;
    public String username;
    public String email;
    public String system_role;
    public String status;
    public String avatar;
}
