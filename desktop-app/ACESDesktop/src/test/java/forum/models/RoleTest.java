package forum.models;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class RoleTest {

    @Test
    void fromDbMapsKnownValuesAndDefaultsToStudent() {
        assertEquals(Role.STUDENT, Role.fromDb("student"));
        assertEquals(Role.LECTURER, Role.fromDb("lecturer"));
        assertEquals(Role.SYSTEM_ADMIN, Role.fromDb("system_admin"));
        assertEquals(Role.STUDENT, Role.fromDb(null));
        assertEquals(Role.STUDENT, Role.fromDb("unknown"));
    }

    @Test
    void fromLabelMapsUiLabelsAndFallsBackToDbValues() {
        assertEquals(Role.STUDENT, Role.fromLabel("Student"));
        assertEquals(Role.LECTURER, Role.fromLabel("Lecturer"));
        assertEquals(Role.SYSTEM_ADMIN, Role.fromLabel("Admin"));
        assertEquals(Role.SYSTEM_ADMIN, Role.fromLabel("system_admin"));
    }
}
