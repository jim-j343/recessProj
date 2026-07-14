package forum.models;

/** System role, mirroring users.system_role in the web-app schema. */
public enum Role {
    STUDENT("student", "Student"),
    LECTURER("lecturer", "Lecturer"),
    SYSTEM_ADMIN("system_admin", "Admin");

    private final String db;    // value stored in the database
    private final String label; // value shown in the UI (segmented control)

    Role(String db, String label) {
        this.db = db;
        this.label = label;
    }

    public String db()    { return db; }
    public String label() { return label; }

    /** Map a database value (e.g. "system_admin") to a Role. */
    public static Role fromDb(String value) {
        if (value == null) return STUDENT;
        for (Role r : values()) if (r.db.equalsIgnoreCase(value.trim())) return r;
        return STUDENT;
    }

    /** Map a UI label (e.g. "Admin") to a Role. */
    public static Role fromLabel(String value) {
        if (value == null) return STUDENT;
        for (Role r : values()) if (r.label.equalsIgnoreCase(value.trim())) return r;
        return fromDb(value);
    }
}
