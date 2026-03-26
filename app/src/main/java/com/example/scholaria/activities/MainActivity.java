package com.example.scholaria.activities;

import android.content.Intent;
import android.os.Bundle;
import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.adapters.AssignmentAdapter;
import com.example.scholaria.adapters.EventAdapter;
import com.example.scholaria.adapters.SubjectAdapter;
import com.example.scholaria.models.Assignment;
import com.example.scholaria.models.Event;
import com.example.scholaria.models.Subject;
import java.util.ArrayList;
import java.util.List;

public class MainActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(android.R.id.content), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        setupNavigation();
        setupSubjects();
        setupAssignments();
        setupEvents();
    }

    private void setupNavigation() {
        findViewById(R.id.navCourses).setOnClickListener(v -> {
            Intent intent = new Intent(this, MyCoursesActivity.class);
            startActivity(intent);
        });
    }

    private void setupSubjects() {
        RecyclerView rv = findViewById(R.id.rvSubjects);
        List<Subject> list = new ArrayList<>();
        list.add(new Subject("Application Development", "CC106", "CN 48125"));
        list.add(new Subject("Web Systems", "WS067", "CN 48126"));
        list.add(new Subject("Data Structures", "CS201", "CN 48127"));

        rv.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        rv.setAdapter(new SubjectAdapter(list));
    }

    private void setupAssignments() {
        RecyclerView rv = findViewById(R.id.rvAssignments);
        List<Assignment> list = new ArrayList<>();
        list.add(new Assignment("Computer Programming 2", "Due: March 23, 2026 | 11:59 PM"));
        list.add(new Assignment("Systems Integration", "Due: March 30, 2026 | 11:59 PM"));
        list.add(new Assignment("Database Management", "Due: April 05, 2026 | 11:59 PM"));

        rv.setLayoutManager(new LinearLayoutManager(this));
        rv.setAdapter(new AssignmentAdapter(list));
    }

    private void setupEvents() {
        RecyclerView rv = findViewById(R.id.rvEvents);
        List<Event> list = new ArrayList<>();
        list.add(new Event("Midterm Exam", "MAR 05", "08:00 AM - 10:00 AM"));
        list.add(new Event("Project Demo", "MAR 12", "01:00 PM - 03:00 PM"));
        list.add(new Event("Tech Seminar", "MAR 20", "09:00 AM - 12:00 PM"));

        rv.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        rv.setAdapter(new EventAdapter(list));
    }
}
