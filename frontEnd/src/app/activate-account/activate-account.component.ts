import { Component, OnInit } from '@angular/core';
import { ActivateAcountService } from './activate-acount.service';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-activate-account',
  templateUrl: './activate-account.component.html',
  styleUrls: ['./activate-account.component.scss']
})
export class ActivateAccountComponent implements OnInit {

  constructor(
    private servicio: ActivateAcountService,
    private route: ActivatedRoute
  ) { }

  token: string

  ngOnInit(): void {
    this.route.params.subscribe((response) => {
      this.token = response["token"];
      if (this.token != '') {
        this.servicio.activateAccount(this.token).subscribe(
          (response) => {
            console.log(response);

            localStorage.setItem("userData", btoa(response.data));
            /* console.log(btoa(response.data));
            console.log(atob(localStorage.getItem('userData'))); */
          },
          (error) => {
            console.log(error);
          }
        )
      } else {
        console.log('No existe token');
      }
    });
  }


}
