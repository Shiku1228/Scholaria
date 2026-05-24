package com.example.scholaria.activities;

import android.os.Bundle;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.viewpager2.widget.ViewPager2;
import com.example.scholaria.R;
import com.example.scholaria.adapters.CourseDetailPagerAdapter;
import com.google.android.material.progressindicator.LinearProgressIndicator;
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;

public class CourseDetailsActivity extends AppCompatActivity {
    public static final String EXTRA_COURSE_ID = "course_id";
    public static final String EXTRA_COURSE_NAME = "course_name";
    public static final String EXTRA_COURSE_NUMBER = "course_number";
    public static final String EXTRA_TEACHER_NAME = "teacher_name";
    public static final String EXTRA_COVER_RES = "cover_res";
    public static final String EXTRA_PROGRESS = "progress";
    public static final String EXTRA_ASSIGNMENTS_TOTAL = "assignments_total";
    public static final String EXTRA_ASSIGNMENTS_SUBMITTED = "assignments_submitted";
    public static final String EXTRA_SEMESTER = "semester";
    public static final String EXTRA_SCHOOL_YEAR = "school_year";
    public static final String EXTRA_INITIAL_TAB = "initial_tab";

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

        String courseId = getIntent().getStringExtra(EXTRA_COURSE_ID);
        String courseName = getIntent().getStringExtra(EXTRA_COURSE_NAME);
        String courseNumber = getIntent().getStringExtra(EXTRA_COURSE_NUMBER);
        String teacherName = getIntent().getStringExtra(EXTRA_TEACHER_NAME);
        String semester = getIntent().getStringExtra(EXTRA_SEMESTER);
        String schoolYear = getIntent().getStringExtra(EXTRA_SCHOOL_YEAR);
        int coverRes = getIntent().getIntExtra(EXTRA_COVER_RES, R.drawable.course_banner_placeholder);
        int progress = getIntent().getIntExtra(EXTRA_PROGRESS, 0);
        int assignmentsTotal = getIntent().getIntExtra(EXTRA_ASSIGNMENTS_TOTAL, 0);
        int assignmentsSubmitted = getIntent().getIntExtra(EXTRA_ASSIGNMENTS_SUBMITTED, 0);

        if (courseName == null) courseName = "Course";
        if (courseNumber == null) courseNumber = "";
        if (teacherName == null) teacherName = "Instructor";
        if (semester == null) semester = "Semester";
        if (schoolYear == null) schoolYear = "School Year";

        ImageView banner = findViewById(R.id.ivCourseBanner);
        banner.setImageResource(coverRes);

        ((TextView) findViewById(R.id.tvCourseTitle)).setText(courseName);
        ((TextView) findViewById(R.id.tvCourseCode)).setText(courseNumber);
        ((TextView) findViewById(R.id.tvCourseTeacher)).setText(teacherName);
        ((TextView) findViewById(R.id.tvCourseTerm)).setText(semester + " | " + schoolYear);
        ((TextView) findViewById(R.id.tvProgressLabel)).setText(progress + "% Completed");
        ((TextView) findViewById(R.id.tvAssignmentsSummary)).setText(assignmentsSubmitted + "/" + assignmentsTotal + " assignments");

        LinearProgressIndicator progressIndicator = findViewById(R.id.courseProgress);
        progressIndicator.setProgress(progress);

        ViewPager2 viewPager = findViewById(R.id.courseViewPager);
        viewPager.setOffscreenPageLimit(1);
        viewPager.setAdapter(new CourseDetailPagerAdapter(this, courseId, courseName, courseNumber));

        TabLayout tabLayout = findViewById(R.id.courseTabLayout);
        new TabLayoutMediator(tabLayout, viewPager, (tab, position) -> {
            switch (position) {
                case 0:
                    tab.setText("Overview");
                    break;
                case 1:
                    tab.setText("Tasks");
                    break;
                case 2:
                    tab.setText("Resources");
                    break;
                case 3:
                    tab.setText("Discussion");
                    break;
                case 4:
                    tab.setText("Live Q&A");
                    break;
                default:
                    tab.setText("Overview");
                    break;
            }
        }).attach();

        int initialTab = getIntent().getIntExtra(EXTRA_INITIAL_TAB, 0);
        viewPager.setCurrentItem(Math.min(Math.max(initialTab, 0), 4), false);
    }
}
