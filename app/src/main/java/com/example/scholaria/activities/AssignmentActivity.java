package com.example.scholaria.activities;

import android.os.Bundle;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.scholaria.R;
import com.example.scholaria.adapters.AssignmentAdapter;
import com.example.scholaria.models.Assignment;
import com.example.scholaria.networks.ApiClient;
import com.example.scholaria.networks.StudentAssignmentDto;
import com.example.scholaria.networks.StudentAssignmentListDataDto;
import com.example.scholaria.networks.StudentApiResponse;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class AssignmentActivity extends AppCompatActivity {
    private final List<Assignment> assignments = new ArrayList<>();
    private AssignmentAdapter adapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_assignment);

        RecyclerView rv = findViewById(R.id.rvAssignments);
        rv.setLayoutManager(new LinearLayoutManager(this));
        adapter = new AssignmentAdapter(assignments);
        rv.setAdapter(adapter);

        TextView subtitle = findViewById(R.id.tvSubtitle);
        if (subtitle != null) {
            subtitle.setText("Live assignments from the student API");
        }

        loadAssignments();
    }

    private void loadAssignments() {
        ApiClient.getInstance(this)
                .getStudentAssignmentsCall()
                .enqueue(new Callback<StudentApiResponse<StudentAssignmentListDataDto>>() {
                    @Override
                    public void onResponse(Call<StudentApiResponse<StudentAssignmentListDataDto>> call, Response<StudentApiResponse<StudentAssignmentListDataDto>> response) {
                        if (!response.isSuccessful() || response.body() == null || response.body().getData() == null) {
                            return;
                        }

                        assignments.clear();
                        for (StudentAssignmentDto dto : response.body().getData().getAssignments()) {
                            assignments.add(new Assignment(
                                    safe(dto.getTitle(), "Assignment"),
                                    safe(dto.getCourseName(), "Course"),
                                    buildDeadline(dto)
                            ));
                        }
                        adapter.notifyDataSetChanged();
                    }

                    @Override
                    public void onFailure(Call<StudentApiResponse<StudentAssignmentListDataDto>> call, Throwable t) {
                        // Leave the list empty if the API call fails.
                    }
                });
    }

    private String buildDeadline(StudentAssignmentDto dto) {
        if (dto.getSubmittedAt() != null && !dto.getSubmittedAt().trim().isEmpty()) {
            return "Submitted: " + dto.getSubmittedAt();
        }
        if (dto.getDueDate() != null && !dto.getDueDate().trim().isEmpty()) {
            return "Due: " + dto.getDueDate();
        }
        return "No due date";
    }

    private String safe(String value, String fallback) {
        return value == null || value.trim().isEmpty() ? fallback : value;
    }
}
