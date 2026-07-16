package forum.api.dto;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import java.util.List;

@JsonIgnoreProperties(ignoreUnknown = true)
public class AdminReportsResponseDto {
    public String filter;
    public List<AdminReportDto> reports;
}
