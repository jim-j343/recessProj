package forum.models;

/** A group/class that owns topics (mirrors the web-app groups table). */
public class Group {
    private long groupId;
    private long adminId;
    private String name;
    private String description;

    public Group() { }
    public Group(long groupId, String name) { this.groupId = groupId; this.name = name; }

    public long getGroupId()          { return groupId; }
    public void setGroupId(long v)     { this.groupId = v; }
    public long getAdminId()          { return adminId; }
    public void setAdminId(long v)     { this.adminId = v; }
    public String getName()           { return name; }
    public void setName(String v)      { this.name = v; }
    public String getDescription()    { return description; }
    public void setDescription(String v){ this.description = v; }
}
