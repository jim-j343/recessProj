package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

/** JSON body returned by /api/login and /api/register: a token + the user. */
@JsonIgnoreProperties(ignoreUnknown = true)
public class AuthResponse {
    public String  token;
    public UserDto user;
}
