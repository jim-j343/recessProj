package forum.util;

import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class PasswordHashTest {

    @Test
    void hashIsDeterministicForTheSamePassword() {
        String first = PasswordHash.of("password123");
        String second = PasswordHash.of("password123");

        assertEquals(first, second);
        assertEquals(64, first.length());
    }

    @Test
    void hashDiffersForDifferentPasswordsAndHandlesNull() {
        String hash = PasswordHash.of("password123");
        String different = PasswordHash.of("password124");
        String empty = PasswordHash.of(null);

        assertNotEquals(hash, different);
        assertNotNull(empty);
        assertEquals(64, empty.length());
        assertNotEquals("password123", hash);
    }
}
