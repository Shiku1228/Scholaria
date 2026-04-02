package com.example.scholaria.activities;

import android.os.Bundle;
import android.widget.TextView;
import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import com.example.scholaria.R;
import com.google.android.material.progressindicator.LinearProgressIndicator;

public class CourseDetailsActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_course_details);

        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowTitleEnabled(false);
        }
        toolbar.setNavigationOnClickListener(v -> finish());

        // Get data from Intent
        String title = getIntent().getStringExtra("COURSE_TITLE");
        String code = getIntent().getStringExtra("COURSE_CODE");
        int progress = getIntent().getIntExtra("COURSE_PROGRESS", 0);

        if (title != null) {
            ((TextView) findViewById(R.id.tvCourseTitle)).setText(title);
        }
        if (code != null) {
            ((TextView) findViewById(R.id.tvCourseCode)).setText(code);
        }

        LinearProgressIndicator progressIndicator = findViewById(R.id.courseProgress);
        progressIndicator.setProgress(progress);
        ((TextView) findViewById(R.id.tvProgressLabel)).setText(progress + "% Completed");
    }
}
