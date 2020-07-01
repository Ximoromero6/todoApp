import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { LoginService } from './login.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss'],
})
export class LoginComponent implements OnInit {
  @ViewChild('loginRef', { static: true }) loginElement: ElementRef;

  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;
  formularioLogin: FormGroup;
  auth2: any;
  showLoader: boolean = true;

  constructor(
    private formBuilder: FormBuilder,
    private login: LoginService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.formularioLogin = this.formBuilder.group({
      email: ['', Validators.required],
      clave: ['', Validators.required],
    });

    //Google Login
    this.googleSDK();
  }

  validarDatosLogin() {
    if (this.formularioLogin.invalid) {
      this.formularioLogin.get('email').markAsDirty();
      this.formularioLogin.get('clave').markAsDirty();
      return;
    }
    console.log(JSON.stringify(this.formularioLogin.value));

    //Función para ver si el usuario está logueado
    this.login
      .validarLogin(
        this.formularioLogin.value.email,
        this.formularioLogin.value.clave
      )
      .subscribe(
        (response) => {
          if (response.status == 1) {
            this.showLoader = false;
            localStorage.setItem("userData", btoa(JSON.stringify(response.data)));
            console.log(response);
            this.router.navigate(['/home']);
          } else {
            this.mensajeNotificacion = response.response;
            this.ocultarNotificacion(false);
          }
        },
        (error) => {
          console.log(error);
        }
      );
  }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }

  //Google Login
  prepareLoginButton() {

    this.auth2.attachClickHandler(this.loginElement.nativeElement, {},
      (googleUser) => {

        let profile = googleUser.getBasicProfile();
        console.log('Token || ' + googleUser.getAuthResponse().id_token);
        console.log('ID: ' + profile.getId());
        console.log('Name: ' + profile.getName());
        console.log('Image URL: ' + profile.getImageUrl());
        console.log('Email: ' + profile.getEmail());

      }, (error) => {
        console.log(JSON.stringify(error, undefined, 2));
      });

  }
  googleSDK() {

    window['googleSDKLoaded'] = () => {
      window['gapi'].load('auth2', () => {
        this.auth2 = window['gapi'].auth2.init({
          client_id: '258969197256-8tgif55q63bf7hqkso8ftoleqgh1a6rt.apps.googleusercontent.com',
          cookiepolicy: 'single_host_origin',
          scope: 'profile email'
        });
        this.prepareLoginButton();
      });
    }

    (function (d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) { return; }
      js = d.createElement(s); js.id = id;
      js.src = "https://apis.google.com/js/platform.js?onload=googleSDKLoaded";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'google-jssdk'));

  }
}
