package com.example.scholaria.fragments;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Toast;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import com.example.scholaria.R;
import com.google.android.material.button.MaterialButton;

public class ProfileFragment extends Fragment {

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_profile, container, false);

        MaterialButton btnEditProfile = view.findViewById(R.id.btnEditProfile);
        MaterialButton btnLogout = view.findViewById(R.id.btnLogout);

        btnEditProfile.setOnClickListener(v -> Toast.makeText(getContext(), "Edit Profile Clicked", Toast.LENGTH_SHORT).show());
        btnLogout.setOnClickListener(v -> Toast.makeText(getContext(), "Logging out...", Toast.LENGTH_SHORT).show());

        return view;
    }
}
