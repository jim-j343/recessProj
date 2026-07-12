package forum.config;

/**
 * Centralized constants for role names, status values, and other enums
 * to prevent string typos and improve maintainability.
 */
public final class AppConstants {
    // Roles
    public static final String ROLE_ADMIN = "admin";
    public static final String ROLE_MEMBER = "member";
    public static final String ROLE_MODERATOR = "moderator";

    // Group/Membership Status
    public static final String STATUS_ACTIVE = "active";
    public static final String STATUS_PENDING = "pending";

    // Quiz Status
    public static final String QUIZ_PUBLISHED = "Published";
    public static final String QUIZ_DRAFT = "Draft";

    // Quality levels
    public static final String QUALITY_HIGH = "High";
    public static final String QUALITY_MEDIUM = "Medium";
    public static final String QUALITY_LOW = "Low";

    private AppConstants() {}
}
