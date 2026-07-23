package forum.models;

/** Local representation of a user (subset of the web-app users table). */
public class User {

    private long userId;
    private String username;
    private String email;
    private Role role;
    private String status;   // active | blacklisted | suspended

    public User() { }

    public User(long userId, String username, String email, Role role, String status) {
        this.userId = userId;
        this.username = username;
        this.email = email;
        this.role = role;
        this.status = status;
    }

    public long getUserId()          { return userId; }
    public void setUserId(long v)     { this.userId = v; }
    public String getUsername()       { return username; }
    public void setUsername(String v) { this.username = v; }
    public String getEmail()          { return email; }
    public void setEmail(String v)    { this.email = v; }
    public Role getRole()             { return role; }
    public void setRole(Role v)       { this.role = v; }
    public String getStatus()         { return status; }
    public void setStatus(String v)   { this.status = v; }

    public String displayName() {
        return (username != null && !username.isBlank()) ? username : email;
    }

    @Override public String toString() {
        return "User{" + userId + ", " + email + ", " + role + "}";
    }
}
