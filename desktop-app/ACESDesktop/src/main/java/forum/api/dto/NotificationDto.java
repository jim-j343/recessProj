package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;
import java.util.List;

@JsonIgnoreProperties(ignoreUnknown = true)
public class NotificationDto {

    @JsonProperty("unread_count")
    public int unreadCount;

    @JsonProperty("notifications")
    public List<Item> notifications;

    @JsonIgnoreProperties(ignoreUnknown = true)
    public static class Item {
        @JsonProperty("notification_id") public long   notificationId;
        @JsonProperty("type")            public String type;
        @JsonProperty("message")         public String message;
        @JsonProperty("is_read")         public boolean isRead;
        @JsonProperty("created_at")      public String createdAt;
    }
}
