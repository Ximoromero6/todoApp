import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { LoginService } from './login/login.service';

@Injectable({
  providedIn: 'root'
})
export class GuardianHomeGuard implements CanActivate {

  constructor(
    private router: Router,
    private LoginService: LoginService
  ) { }

  canActivate() {
    let userData;
    // if (this.checkData()) data = JSON.parse(atob(localStorage.getItem('userData')));

    try {
      if (localStorage.getItem('userData') != null) {
        userData = JSON.parse(atob(localStorage.getItem('userData')));
      } else {
        userData = JSON.parse(atob(sessionStorage.getItem('userData')));
      }

      this.LoginService.isLogged(JSON.stringify(userData))
        .subscribe(
          (response) => {
            console.log(`RESPONSE STATUS: ${response.response}`);
            if (response.status === 0) {
              this.router.navigate(['']);
              return false;
            }
          },
          (error) => {
            console.log(error);
          }
        );
      return true;
      /*  data = JSON.parse(atob(localStorage.getItem('userData')));
       console.log(data); */

      /*  if (!this.LoginService.isLogged(data)) {
  
      console.log('No estás logueado');
      this.router.navigate(['']);
      return false;
    } */

    } catch (e) {
      this.router.navigate(['']);
      return false;
      console.log(e);
    }
  }

  checkData() {

    if (localStorage.getItem('userData') != null) {

      if (!this.isEmpty(localStorage.getItem('userData'))) {

        if (this.isJson(atob(localStorage.getItem('userData')))) {

          let data = JSON.parse(atob(localStorage.getItem('userData')));
          console.log(data.email);
          if (data.email === null || data.nombre === null || data.token === null || data.verificado === null) {
            console.log('test');
            return false;
          }

        } else
          return false;

      } else
        return false;

    } else
      return false;

    return true;

  }

  isEmpty(obj) {
    for (let prop in obj) {
      if (obj.hasOwnProperty(prop))
        return false;

    }
    return true;
  }

  isJson(str) {
    try {
      JSON.parse(str);
    } catch (e) {
      return false;
    }
    return true;
  }



}
