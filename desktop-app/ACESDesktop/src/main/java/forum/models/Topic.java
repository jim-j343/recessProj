package forum.models;

/** A discussion topic (mirrors the web-app topics table). */
public class Topic {
    private long topicId;
    private long groupId;
    private long creatorId;
    private String title;
    private String category;
    private boolean flagged;
    private String createdAt;

    // transient display helpers (not stored)
    private String authorName;
    private int replyCount;

    public Topic() { }

    public long getTopicId()          { return topicId; }
    public void setTopicId(long v)     { this.topicId = v; }
    public long getGroupId()          { return groupId; }
    public void setGroupId(long v)     { this.groupId = v; }
    public long getCreatorId()        { return creatorId; }
    public void setCreatorId(long v)   { this.creatorId = v; }
    public String getTitle()          { return title; }
    public void setTitle(String v)     { this.title = v; }
    public String getCategory()       { return category; }
    public void setCategory(String v)  { this.category = v; }
    public boolean isFlagged()        { return flagged; }
    public void setFlagged(boolean v)  { this.flagged = v; }
    public String getCreatedAt()      { return createdAt; }
    public void setCreatedAt(String v) { this.createdAt = v; }
    public String getAuthorName()     { return authorName; }
    public void setAuthorName(String v){ this.authorName = v; }
    public int getReplyCount()        { return replyCount; }
    public void setReplyCount(int v)   { this.replyCount = v; }
}
