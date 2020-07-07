import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { Location } from '@angular/common';


@Component({
  selector: 'app-send-email',
  templateUrl: './send-email.component.html',
  styleUrls: ['./send-email.component.scss']
})
export class SendEmailComponent implements OnInit {

  data

  constructor(
    private ruta: Router,
    private location: Location) { }

  ngOnInit(): void {
    this.data = this.location.getState();
    console.log(this.data);
    console.log(this.data.email);
  }

}
