package forum.models;

<<<<<<< HEAD
import java.util.List;

=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
/** A post / threaded reply (mirrors the web-app posts table, incl. is_synced). */
public class Post {
    private long postId;
    private long topicId;
    private long authorId;
    private Long parentPostId;   // nullable
    private String content;
    private boolean synced;
    private String createdAt;

    // transient
    private String authorName;

<<<<<<< HEAD
    // transient, in-memory only — not persisted to the local cache. Populated
    // straight from the server response for posts you authored; not saved to
    // SQLite, so it only reflects what's been fetched this session.
    private List<String> excludedUsernames;

=======
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
    public Post() { }

    public long getPostId()            { return postId; }
    public void setPostId(long v)       { this.postId = v; }
    public long getTopicId()           { return topicId; }
    public void setTopicId(long v)      { this.topicId = v; }
    public long getAuthorId()          { return authorId; }
    public void setAuthorId(long v)     { this.authorId = v; }
    public Long getParentPostId()      { return parentPostId; }
    public void setParentPostId(Long v){ this.parentPostId = v; }
    public String getContent()         { return content; }
    public void setContent(String v)    { this.content = v; }
    public boolean isSynced()          { return synced; }
    public void setSynced(boolean v)    { this.synced = v; }
    public String getCreatedAt()       { return createdAt; }
    public void setCreatedAt(String v)  { this.createdAt = v; }
    public String getAuthorName()      { return authorName; }
    public void setAuthorName(String v){ this.authorName = v; }
<<<<<<< HEAD
    public List<String> getExcludedUsernames()      { return excludedUsernames; }
    public void setExcludedUsernames(List<String> v){ this.excludedUsernames = v; }
}
=======
}
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
