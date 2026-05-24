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
import com.example.scholaria.adapters.CourseAdapter;
import com.example.scholaria.models.Course;
import java.util.ArrayList;
import java.util.List;

public class MyCoursesActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_my_courses);

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(android.R.id.content), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        setupNavigation();
        setupCourses();
    }

    private void setupNavigation() {
        findViewById(R.id.navHome).setOnClickListener(v -> {
            Intent intent = new Intent(this, MainActivity.class);
            startActivity(intent);
            finish();
        });
    }

    private void setupCourses() {
        RecyclerView rv = findViewById(R.id.rvMyCourses);
        List<Course> list = new ArrayList<>();
        list.add(new Course("CRS-106", "Application Development", "CC106", "1st Semester", "2026-2027", 75, 12, 15, "Prof. Santos", "Enrolled", R.drawable.course_banner_placeholder));
        list.add(new Course("CRS-067", "Web Systems", "WS067", "1st Semester", "2026-2027", 40, 5, 12, "Prof. Reyes", "Enrolled", R.drawable.course_banner_placeholder));
        list.add(new Course("CRS-201", "Data Structures", "CS201", "1st Semester", "2026-2027", 10, 1, 10, "Prof. Lim", "Enrolled", R.drawable.course_banner_placeholder));

        rv.setLayoutManager(new LinearLayoutManager(this));
        rv.setAdapter(new CourseAdapter(list));
    }
}
