package forum.database;

import forum.config.DatabaseConfig;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.sql.Statement;

/** Opens connections to the local SQLite cache and initialises its schema. */
public final class SQLiteConnection {

    private SQLiteConnection() { }

    static {
        try {
            Class.forName("org.sqlite.JDBC");
        } catch (ClassNotFoundException e) {
            throw new IllegalStateException("SQLite JDBC driver not found", e);
        }
    }

    public static Connection get() throws SQLException {
        DatabaseConfig.ensureAppDir();
        Connection c = DriverManager.getConnection(DatabaseConfig.jdbcUrl());
        try (Statement st = c.createStatement()) {
            st.execute("PRAGMA foreign_keys = ON");
            st.execute("PRAGMA busy_timeout = 5000");
        }
        return c;
    }

    /** Creates the local cache tables if they do not yet exist. */
    public static void initSchema() {
        String users = """
            CREATE TABLE IF NOT EXISTS users (
                user_id       INTEGER PRIMARY KEY AUTOINCREMENT,
                username      TEXT,
                email         TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                system_role   TEXT NOT NULL DEFAULT 'student',
                status        TEXT NOT NULL DEFAULT 'active',
                avatar        TEXT,
                server_id     INTEGER,
                updated_at    TEXT DEFAULT CURRENT_TIMESTAMP
            )""";

        String groups = """
            CREATE TABLE IF NOT EXISTS \"groups\" (
                group_id    INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id    INTEGER,
                name        TEXT NOT NULL,
                description TEXT,
                server_id   INTEGER
            )""";

        String topics = """
            CREATE TABLE IF NOT EXISTS topics (
                topic_id   INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id   INTEGER NOT NULL,
                creator_id INTEGER NOT NULL,
                title      TEXT NOT NULL,
                category   TEXT,
                is_flagged INTEGER NOT NULL DEFAULT 0,
                server_id  INTEGER,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )""";

        String posts = """
            CREATE TABLE IF NOT EXISTS posts (
                post_id        INTEGER PRIMARY KEY AUTOINCREMENT,
                topic_id       INTEGER NOT NULL,
                author_id      INTEGER NOT NULL,
                author_name    TEXT,
                parent_post_id INTEGER,
                content        TEXT NOT NULL,
                is_flagged     INTEGER NOT NULL DEFAULT 0,
                is_synced      INTEGER NOT NULL DEFAULT 0,
                server_id      INTEGER,
                created_at     TEXT DEFAULT CURRENT_TIMESTAMP
            )""";

        String syncLog = """
            CREATE TABLE IF NOT EXISTS sync_log (
                sync_id     INTEGER PRIMARY KEY AUTOINCREMENT,
                table_name  TEXT NOT NULL,
                local_id    INTEGER NOT NULL,
                server_id   INTEGER,
                status      TEXT NOT NULL DEFAULT 'pending',
                synced_at   TEXT,
                created_at  TEXT DEFAULT CURRENT_TIMESTAMP
            )""";

        try (Connection c = get(); Statement st = c.createStatement()) {
            st.execute(users);
            st.execute(groups);
            st.execute(topics);
            st.execute(posts);
            st.execute(syncLog);
            
            try {
                st.execute("ALTER TABLE topics ADD COLUMN reply_count INTEGER NOT NULL DEFAULT 0");
            } catch (SQLException ignore) {
                // column likely already exists
            }
            
            try {
                st.execute("ALTER TABLE posts ADD COLUMN author_name TEXT");
            } catch (SQLException ignore) {
                // column likely already exists
            }

            try {
                st.execute("ALTER TABLE users ADD COLUMN avatar TEXT");
            } catch (SQLException ignore) {
                // column likely already exists
            }
        } catch (SQLException e) {
            throw new IllegalStateException("Failed to initialise local schema", e);
        }
    }
}
